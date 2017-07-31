import React from 'react';

export default function Loading({ title }) {
  return (
    <div className="container-fluid cover">
      <div className="row">
        <div className="col-sm-12">
          <div className="progress">
            <div className="progress-bar progress-bar-striped active" role="progressbar" style={{ width: '100%' }}>
              <span className="sr-only"><span className="fa fa-spinner"></span></span>
            </div>
          </div>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12 text-center">
          <h1 className="text-muted">{title}&hellip;</h1>
        </div>
      </div>
    </div>
  );
};
