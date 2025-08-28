const express = require('express');
const fs = require('fs');
const path = require('path');
const https = require('https');

const app = express();
const port = 3700;

const templatesDir = path.join(__dirname, '..', 'src', 'templates');

const getTemplate = (name) => {
    return JSON.stringify(fs.readFileSync(path.join(templatesDir, name + '.html'), 'utf8'));
};

const translations = fs.readFileSync(path.join(__dirname, 'resources/lang/en.json'), 'utf8');

let template = fs.readFileSync(path.join(__dirname, 'index.html'), 'utf8');

template = template.replace("'%default_translations%'", translations);
template = template.replace("'%current_lang_translations%'", translations);
template = template.replace("'%sidebar%'", getTemplate('sidebar'));

app.use(express.static(path.join(__dirname, 'resources')));

app.get('/', (req, res) => {
    res.send(template);
});
app.get('/design-demo', (req, res) => {
    res.send(fs.readFileSync(path.join(__dirname, 'design-demo', 'index.html'), 'utf8'));
});

const privateKey = fs.readFileSync(path.join(__dirname, '..', 'mock-api', 'src', 'server.key'), 'utf8');
const certificate = fs.readFileSync(path.join(__dirname, '..', 'mock-api', 'src', 'server.cert'), 'utf8');

const httpsServer = https.createServer({ key: privateKey, cert: certificate }, app);

httpsServer.listen(port, 'localhost', () => {
    console.log(`Demo app listening on https://localhost:${port}/`);
});
