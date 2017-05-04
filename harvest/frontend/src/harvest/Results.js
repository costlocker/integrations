import React from 'react';

export default function Results({ importResult }) {
  if (!importResult) {
    return <strong>Importing...</strong>
  } else if (importResult.hasSucceed) {
    return <div className="bg-success text-center">
        <hr />
        <strong>Project was imported</strong><br />
        <a href={importResult.projectUrl} target="_blank" className="btn btn-success">
          Go to Costlocker
        </a>
        <hr />
      </div>;
  } else {
    return <div className="bg-danger text-center">
        <hr />
        <strong>Import has failed</strong>
        <hr />
      </div>;
  }
}
