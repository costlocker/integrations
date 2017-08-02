import React from 'react';
import { pushToApi } from '../api';
import { trans } from '../i18n';

const buildError = (jsError, currentUser) => ({
  date: (new Date()).toString(),
  error: jsError.toString(),
  stack: jsError.stack,
  user: {
    username: currentUser ? currentUser.person.email : 'anonymous',
  },
});

const encodeError = data => btoa(unescape(encodeURIComponent(JSON.stringify(data, null, 2))));

const currentUrl = window.location.href;

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
              <h1 className="panel-title">{trans('error.title')}</h1>
            </div>
            <div className="panel-body">
              {trans('error.description', { currentUrl })} <a href="mailto: support@costlocker.com">
                support@costlocker.com</a>.
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
