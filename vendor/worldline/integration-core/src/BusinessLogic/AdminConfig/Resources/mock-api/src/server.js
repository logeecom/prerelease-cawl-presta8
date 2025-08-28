const https = require('https');
const path = require('path');
const fs = require('fs');
const express = require('express');
const { setRoutes } = require('./routes');
const bodyParser = require('body-parser');
const multer = require('multer');

const keyFile = path.join(__dirname, 'server.key');
const certFile = path.join(__dirname, 'server.cert');

// noinspection JSUnusedGlobalSymbols
const storage = multer.diskStorage({
    destination: function (req, file, cb) {
        cb(null, './uploads/');
    },
    filename: function (req, file, cb) {
        let ext = file.originalname.substring(file.originalname.lastIndexOf('.'), file.originalname.length);
        cb(null, Date.now() + ext);
    }
});

const server = express();
server.use(express.static('uploads'));
server.use(bodyParser.json());
server.use(multer({ storage }).any());

server.use(function (req, res, next) {
    res.setHeader('Access-Control-Allow-Origin', '*');
    res.setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, PATCH, DELETE');
    res.setHeader('Access-Control-Allow-Headers', 'X-Requested-With,content-type');
    res.setHeader('Access-Control-Allow-Credentials', 'true');
    next();
});

const delay = process.argv.length > 2 ? Number(process.argv[2]) : false;

server.use(function (req, res, next) {
    if (req.method !== 'OPTIONS' && delay) {
        setTimeout(next, delay);
    } else {
        next();
    }
});

setRoutes(server);

/** Default route handler */
server.use((req, res) => {
    if (req.method === 'OPTIONS') {
        res.status(200).json({});
    } else {
        res.status(404).json({ message: 'Not found' });
    }
});

https
    .createServer(
        {
            key: fs.readFileSync(keyFile),
            cert: fs.readFileSync(certFile)
        },
        server
    )
    .listen(3750, () => {
        console.log('API URL https://localhost:3750/');
        delay && console.log(`API responses will be delayed for ${delay}ms.`);
    });
