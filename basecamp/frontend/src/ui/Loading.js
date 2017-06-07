import React from 'react';

export default function Loading() {
  return (
    <div className="container-fluid cover">
      <div className="row">
        <div className="col-sm-12">
          <div className="progress">
            <div className="progress-bar progress-bar-striped active" role="progressbar" style={{width: '100%'}}>
              <span className="sr-only">Loading...</span>
            </div>
          </div>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12 text-center">
          <h1 className="text-muted">Loading Costlocker & Basecamp integration&hellip;</h1>
        </div>
      </div>
    </div>
  );
};
