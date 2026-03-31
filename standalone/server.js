const http = require('http');
const fs = require('fs');
const path = require('path');

const PORT = 8899;
const DATA_FILE = path.join(__dirname, 'data', 'usage.json');
const HTML_FILE = __dirname + '/index.html';

const MIME_TYPES = {
  '.html': 'text/html',
  '.js': 'application/javascript',
  '.css': 'text/css',
  '.json': 'application/json'
};

function normalizeUsageData(raw) {
  const data = raw && typeof raw === 'object' ? raw : {};
  const recentSends = Array.isArray(data.recentSends) ? data.recentSends : [];
  const locations = data.locations && typeof data.locations === 'object' ? data.locations : {};
  const senators = data.senators && typeof data.senators === 'object' ? data.senators : {};

  for (const item of recentSends) {
    if (!item || typeof item !== 'object') continue;
    const senator = typeof item.senator === 'string' ? item.senator.trim() : 'Unknown Senator';
    const city = typeof item.city === 'string' ? item.city.trim() : '';
    const zip = typeof item.zip === 'string' || typeof item.zip === 'number' ? String(item.zip).trim() : '';
    if (!city && !zip) continue;

    const key = `${city.toLowerCase()}|${zip}`;
    const time = typeof item.time === 'string' ? item.time : new Date().toISOString();
    if (!locations[key]) {
      locations[key] = {
        city,
        zip,
        count: 0,
        firstSent: time,
        lastSent: time
      };
    }

    locations[key].count += 1;
    if (!locations[key].city && city) locations[key].city = city;
    if (!locations[key].zip && zip) locations[key].zip = zip;
    if (!locations[key].firstSent || time < locations[key].firstSent) locations[key].firstSent = time;
    if (!locations[key].lastSent || time > locations[key].lastSent) locations[key].lastSent = time;

    if (!senators[senator]) {
      senators[senator] = {
        name: senator,
        count: 0,
        firstSent: time,
        lastSent: time,
        locations: {}
      };
    }
    const senatorEntry = senators[senator];
    senatorEntry.count += 1;
    if (!senatorEntry.firstSent || time < senatorEntry.firstSent) senatorEntry.firstSent = time;
    if (!senatorEntry.lastSent || time > senatorEntry.lastSent) senatorEntry.lastSent = time;
    if (!senatorEntry.locations[key]) {
      senatorEntry.locations[key] = {
        city,
        zip,
        count: 0,
        firstSent: time,
        lastSent: time
      };
    }
    senatorEntry.locations[key].count += 1;
    if (!senatorEntry.locations[key].city && city) senatorEntry.locations[key].city = city;
    if (!senatorEntry.locations[key].zip && zip) senatorEntry.locations[key].zip = zip;
    if (!senatorEntry.locations[key].firstSent || time < senatorEntry.locations[key].firstSent) senatorEntry.locations[key].firstSent = time;
    if (!senatorEntry.locations[key].lastSent || time > senatorEntry.locations[key].lastSent) senatorEntry.locations[key].lastSent = time;
  }

  return {
    count: Number.isFinite(data.count) ? data.count : recentSends.length,
    recentSends,
    locations,
    senators
  };
}

function readData() {
  try {
    const data = fs.readFileSync(DATA_FILE, 'utf8');
    return normalizeUsageData(JSON.parse(data));
  } catch (e) {
    return normalizeUsageData({ count: 0, recentSends: [], locations: {} });
  }
}

function writeData(data) {
  fs.writeFileSync(DATA_FILE, JSON.stringify(normalizeUsageData(data), null, 2));
}

const server = http.createServer((req, res) => {
  // CORS headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type');

  if (req.method === 'OPTIONS') {
    res.writeHead(204);
    res.end();
    return;
  }

  // GET usage data
  if (req.method === 'GET' && req.url === '/api/usage') {
    const data = readData();
    res.writeHead(200, { 'Content-Type': 'application/json' });
    res.end(JSON.stringify(data));
    return;
  }

  // POST new send
  if (req.method === 'POST' && req.url === '/api/usage') {
    let body = '';
    req.on('data', chunk => body += chunk);
    req.on('end', () => {
      try {
        const { senator, city, zip } = JSON.parse(body);
        const data = readData();
        const normalizedCity = typeof city === 'string' ? city.trim() : '';
        const normalizedZip = typeof zip === 'string' || typeof zip === 'number' ? String(zip).trim() : '';
        const time = new Date().toISOString();

        data.count += 1;
        data.recentSends.unshift({
          senator,
          city: normalizedCity,
          zip: normalizedZip,
          time
        });
        data.recentSends = data.recentSends.slice(0, 10);

        if (normalizedCity || normalizedZip) {
          const locationKey = `${normalizedCity.toLowerCase()}|${normalizedZip}`;
          const existing = data.locations[locationKey] || {
            city: normalizedCity,
            zip: normalizedZip,
            count: 0,
            firstSent: time,
            lastSent: time
          };

          existing.city = existing.city || normalizedCity;
          existing.zip = existing.zip || normalizedZip;
          existing.count += 1;
          existing.firstSent = existing.firstSent || time;
          existing.lastSent = time;
          data.locations[locationKey] = existing;

          const senatorKey = typeof senator === 'string' && senator.trim() ? senator.trim() : 'Unknown Senator';
          data.senators = data.senators && typeof data.senators === 'object' ? data.senators : {};
          const senatorEntry = data.senators[senatorKey] || {
            name: senatorKey,
            count: 0,
            firstSent: time,
            lastSent: time,
            locations: {}
          };
          senatorEntry.count += 1;
          senatorEntry.firstSent = senatorEntry.firstSent || time;
          senatorEntry.lastSent = time;
          senatorEntry.locations = senatorEntry.locations && typeof senatorEntry.locations === 'object' ? senatorEntry.locations : {};
          const senatorLocation = senatorEntry.locations[locationKey] || {
            city: normalizedCity,
            zip: normalizedZip,
            count: 0,
            firstSent: time,
            lastSent: time
          };
          senatorLocation.city = senatorLocation.city || normalizedCity;
          senatorLocation.zip = senatorLocation.zip || normalizedZip;
          senatorLocation.count += 1;
          senatorLocation.firstSent = senatorLocation.firstSent || time;
          senatorLocation.lastSent = time;
          senatorEntry.locations[locationKey] = senatorLocation;
          data.senators[senatorKey] = senatorEntry;
        }

        writeData(data);
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ success: true, count: data.count, locations: data.locations, senators: data.senators }));
      } catch (e) {
        res.writeHead(400, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ error: e.message }));
      }
    });
    return;
  }

  // Serve static files
  let filePath = req.url === '/' ? '/index.html' : req.url;
  filePath = path.join(__dirname, filePath);
  
  const ext = path.extname(filePath);
  const contentType = MIME_TYPES[ext] || 'text/plain';

  try {
    const content = fs.readFileSync(filePath);
    res.writeHead(200, { 'Content-Type': contentType });
    res.end(content);
  } catch (e) {
    res.writeHead(404);
    res.end('Not found');
  }
});

server.listen(PORT, () => {
  console.log(`ACA7 Email Tool running at http://localhost:${PORT}`);
  console.log(`API: http://localhost:${PORT}/api/usage`);
});
