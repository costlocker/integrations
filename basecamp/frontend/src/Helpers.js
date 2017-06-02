import React from 'react';

const Errors = ({ title, error }) => {
  if (!error) {
    return null;
  }
  return (
    <div className="panel panel-danger">
      <div className="panel-heading">
        {title}
      </div>
      <div className="panel-body">
        {decodeURIComponent(error)}
      </div>
    </div>
  );
};

const ExternalLink = ({ url }) => (
  <a href={url} target="_blank" rel="noopener noreferrer"><i className="fa fa-external-link"></i></a>
);

export { Errors, ExternalLink };
