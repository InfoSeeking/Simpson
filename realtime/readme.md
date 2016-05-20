## SIMPSON Realtime ##

Ensure you have NodeJS and the NodeJS package manager npm installed. See [the NodeJS website](https://nodejs.org/en/) for downloads and installation instructions.

Copy over the `config.js.example` to `config.js` as follows:
```
cd realtime
cp config.js.example config.js
```

To install the dependencies for the realtime server, in terminal cd to the realtime directory and run `npm install` as follows

```
cd realtime
npm install
```

To start the server, run `npm start` in the terminal.

Lastly, you need to let SIMPSON Core know about the realtime server. Modify the .env file of SIMPSON Core to set the environment variable REALTIME\_SERVER to the URL where the realtime server is running. If you're running this on your local machine, you'll likely want to set it to

```
REALTIME_SERVER=http://localhost:3000
```
