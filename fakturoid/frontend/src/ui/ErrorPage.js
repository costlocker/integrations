import React from 'react';
import { pushToApi } from '../api';

const buildError = (jsError, currentUser) => ({
  error: jsError.toString(),
  stack: jsError.stack,
  user: {
    username: currentUser ? currentUser.person.email : 'anonymous',
  },
});

const encodeError = data => btoa(unescape(encodeURIComponent(JSON.stringify(data, null, 2))));

export default function ErrorPage({ appState, isDevelopmentMode }) {
  const jsError = appState.cursor(['app', 'error']).deref();
  const data = buildError(jsError, appState.cursor(['auth', 'costlocker']).deref());

  if (isDevelopmentMode) {
    console.error(jsError);
  }
  pushToApi('/log', data).catch(() => console.log('Error not saved'));

  return (
    <div className="container text-center">
      <div className="row">
        <div className="col-sm-6 col-sm-offset-3">
          <div className="panel panel-danger error-page">
            <div className="panel-heading">
              <h1 className="panel-title">Oops, something went wrong</h1>
            </div>
            <div className="panel-body">
              Try <a href="./">reload the page</a>.
              If the error is happening again, then
              sends us following information as text to <a href="mailto: support@costlocker.com">support@costlocker.com</a>.
            </div>
            <div className="panel-footer">
              <pre><code>{encodeError(data)}</code></pre>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}
