import React from 'react';
import Clipboard from 'clipboard';

let isClipboardLoaded = false;
const initClipboard = () => {
  if (isClipboardLoaded) {
    return;
  }
  new Clipboard('#copy-to-clipboard');
  isClipboardLoaded = true;
}

const getCurlCommand = (appState) => {
  const login = appState.cursor(['login']).deref();
  const curl = appState.cursor(['curl']).deref();
  const data = curl.get('method') === 'POST' ? ` -d '${JSON.stringify(curl.get('request')())}'` : '';
  return `curl -X ${curl.get('method')} "${ curl.get('endpoint')}"${data} -u "test_webhooks:${login.get('token')}"`;
}

export default function CurlExample({ appState }) {
  initClipboard();
  return <div className="row">
    <div className="col-sm-10">
      <div className="form-group">
        <div className="input-group">
          <label className="input-group-addon" htmlFor="curl">API</label>
          <input type="text" className="form-control" id="curl"
            value={getCurlCommand(appState)} readOnly />
        </div>
      </div>
    </div>
    <div className="col-sm-2">
      <button id="copy-to-clipboard" className="btn btn-default btn-block" data-clipboard-target="#curl">Copy & try it!</button>
    </div>
  </div>;
}
