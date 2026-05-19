require('dotenv').config();
const express = require('express');

const app = express();
const port = process.env.PORT || 3000;

app.get('/', (_req, res) => {
  res.json({
    status: 'ok',
    service: 'dodidis-media-event node sidecar',
    message: 'Node.js is running alongside the PHP website.'
  });
});

app.listen(port, () => {
  console.log(`Node sidecar listening on port ${port}`);
});
