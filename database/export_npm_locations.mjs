import { counties, constituencies, wards, subCounties, localities, areas, getConstituencyOfWard, getCountyOfWard } from 'kenya-locations';
import { writeFile } from 'node:fs/promises';

const key = (value) => String(value).trim().toLowerCase().replace(/[^a-z0-9]+/g, '');
const units = [];
const countyKeys = new Map();
const constituencyKeys = new Map();
const localityKeys = new Map();

for (const county of counties) {
    const unitKey = `county:${county.code}`;
    countyKeys.set(key(county.name), unitKey);
    units.push({ key: unitKey, parent_key: null, unit_type: 'county', code: county.code, name: county.name, source: 'kenya-locations@0.9.0' });
}

for (const constituency of constituencies) {
    const countyKey = countyKeys.get(key(constituency.county));
    if (!countyKey) continue;
    const unitKey = `constituency:${constituency.code}`;
    constituencyKeys.set(`${key(constituency.county)}:${key(constituency.name)}`, unitKey);
    units.push({ key: unitKey, parent_key: countyKey, unit_type: 'constituency', code: constituency.code, name: constituency.name, source: 'kenya-locations@0.9.0' });
}

for (const subCounty of subCounties) {
    const countyKey = countyKeys.get(key(subCounty.county));
    if (countyKey) units.push({ key: `sub_county:${subCounty.code}`, parent_key: countyKey, unit_type: 'sub_county', code: subCounty.code, name: subCounty.name, source: 'kenya-locations@0.9.0' });
}

for (const ward of wards) {
    const wardConstituency = getConstituencyOfWard(ward.name)?.data;
    const wardCounty = getCountyOfWard(ward.name)?.name;
    const constituencyKey = wardConstituency && wardCounty ? constituencyKeys.get(`${key(wardCounty)}:${key(wardConstituency.name)}`) : null;
    if (constituencyKey) {
        units.push({ key: `ward:${ward.code}`, parent_key: constituencyKey, unit_type: 'ward', code: ward.code, name: ward.name, source: 'kenya-locations@0.9.0' });
    }
}

for (const locality of localities) {
    const countyKey = countyKeys.get(key(locality.county));
    if (!countyKey) continue;
    const unitKey = `location:${key(locality.county)}:${key(locality.name)}`;
    localityKeys.set(`${key(locality.county)}:${key(locality.name)}`, unitKey);
    units.push({ key: unitKey, parent_key: countyKey, unit_type: 'location', code: null, name: locality.name, source: 'kenya-locations@0.9.0' });
}

for (const area of areas) {
    const parentKey = localityKeys.get(`${key(area.county)}:${key(area.locality)}`);
    if (parentKey) {
        units.push({ key: `sub_location:${key(area.county)}:${key(area.locality)}:${key(area.name)}`, parent_key: parentKey, unit_type: 'sub_location', code: null, name: area.name, source: 'kenya-locations@0.9.0' });
    }
}

await writeFile('database/data/kenya-locations.json', JSON.stringify({ generated_at: new Date().toISOString(), source: 'kenya-locations@0.9.0', units }, null, 2));
console.log(`Exported ${units.length} administrative units.`);
