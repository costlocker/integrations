import React from 'react';

const defaultTitle = 'Loading Costlocker webhooks manager';

export default function Loading({ title }) {
  return (
    <div className="container-fluid cover">
      <div className="row">
        <div className="col-sm-12">
          <div className="progress">
            <div className="progress-bar progress-bar-striped active" role="progressbar" style={{ width: '100%' }}>
              <span className="sr-only">Loading...</span>
            </div>
          </div>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12 text-center">
          <h1 className="text-muted">{title || defaultTitle}&hellip;</h1>
        </div>
      </div>
    </div>
  );
};
