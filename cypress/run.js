require('child_process').spawn('bash', ['cypress/run.sh'], {
    cwd: process.cwd(),
    detached: true,
    stdio: 'inherit'
});
