import { test } from 'node:test';
import assert from 'node:assert/strict';
import { spawn } from 'node:child_process';
import { mkdtemp, writeFile, rm, readdir } from 'node:fs/promises';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import net from 'node:net';
import http from 'node:http';

// Use a fresh connection for each request to PHP's development server.
function fetch(url, { method = 'GET', body } = {}) {
  return new Promise((resolve, reject) => {
    const request = http.request(url, { method, agent: false }, response => {
      let text = '';
      response.setEncoding('utf8');
      response.on('data', chunk => { text += chunk; });
      response.on('error', reject);
      response.on('end', () => resolve({
        status: response.statusCode,
        headers: { get: name => response.headers[name] ?? null },
        text: async () => text,
        json: async () => JSON.parse(text),
      }));
    });
    request.on('error', reject);
    request.end(body);
  });
}

const root = fileURLToPath(new URL('../', import.meta.url));

test('HTTP methods, preflight, and JSON object validation', async () => {
  const reservation = net.createServer();
  await new Promise(resolve => reservation.listen(0, '127.0.0.1', resolve));
  const port = reservation.address().port;
  await new Promise(resolve => reservation.close(resolve));
  const temp = await mkdtemp(path.join(tmpdir(), 'majilink-http-'));
  const phpPath = value => JSON.stringify(value.replaceAll('\\', '/'));
  const router = path.join(temp, 'router.php');
  await writeFile(router, `<?php
if (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) === '/test-json') {
    require ${phpPath(path.join(root, 'src/response.php'))};
    json_response(['data' => request_json()]);
}
require ${phpPath(path.join(root, 'public/index.php'))};
`);
  const server = spawn(process.env.PHP_BINARY || 'php', ['-S', `127.0.0.1:${port}`, router], { cwd: root, stdio: 'ignore' });
  let launchError;
  server.on('error', error => { launchError = error; });
  const base = `http://127.0.0.1:${port}`;
  try {
    let ready = false;
    for (let attempt = 0; attempt < 100; attempt++) {
      if (launchError) throw launchError;
      try { await fetch(base); ready = true; break; } catch {}
      await new Promise(resolve => setTimeout(resolve, 50));
    }
    assert.ok(ready, 'PHP server must start');
    let endpoints = 0;
    for (const directory of await readdir(path.join(root, 'api'))) {
      for (const filename of await readdir(path.join(root, 'api', directory))) {
        if (!filename.endsWith('.php')) continue;
        const name = filename.slice(0, -4);
        const methods = ['list', 'me'].includes(name) ? ['GET'] : ['status', 'update-status', 'update-scope'].includes(name) ? ['POST', 'PATCH'] : ['POST'];
        const url = `${base}/api/${directory}/${filename}`;
        for (const method of ['GET', 'POST', 'PATCH', 'PUT', 'DELETE'].filter(value => !methods.includes(value))) {
          const response = await fetch(url, { method });
          assert.equal(response.status, 405, `${method} ${url}`);
          assert.equal(response.headers.get('allow'), [...methods, 'OPTIONS'].join(', '));
          assert.deepEqual(await response.json(), { error: 'Method not allowed' });
        }
        const preflight = await fetch(url, { method: 'OPTIONS' });
        assert.equal(preflight.status, 204);
        assert.equal(await preflight.text(), '');
        assert.equal(preflight.headers.get('access-control-allow-methods'), [...methods, 'OPTIONS'].join(', '));
        assert.equal(preflight.headers.get('access-control-allow-origin'), '*');
        endpoints++;
      }
    }
    assert.ok(endpoints > 0);
    for (const body of ['', ' ', '{', '[]', '[1]', 'null', 'true', '42', '"text"']) {
      const response = await fetch(`${base}/test-json`, { method: 'POST', body });
      assert.equal(response.status, 400, `Invalid body: ${body}`);
      assert.equal(typeof (await response.json()).error, 'string');
    }
    for (const body of ['{}', '{"name":"Jane","nested":{"values":[1,2]}}', '{"0":"value"}']) {
      const response = await fetch(`${base}/test-json`, { method: 'POST', body });
      assert.equal(response.status, 200, `Valid object: ${body}`);
      assert.ok(Object.hasOwn(await response.json(), 'data'));
    }
  } finally {
    if (server.exitCode === null && server.pid) {
      await new Promise(resolve => { server.once('exit', resolve); server.kill(); });
    }
    await rm(temp, { recursive: true, force: true });
  }
});
