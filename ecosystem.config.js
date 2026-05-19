module.exports = {
  apps: [
    {
      name: 'dodidis-media-event-node-sidecar',
      script: './app.js',
      instances: 1,
      autorestart: true,
      watch: false,
      env: {
        NODE_ENV: 'production',
        PORT: 3000
      }
    }
  ]
};
