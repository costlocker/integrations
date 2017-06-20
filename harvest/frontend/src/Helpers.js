import React from 'react';

const FullButton = ({ text, onClick }) => (
  <div className="bg-success text-center">
    <hr />
    <button className="btn btn-success btn-lg" onClick={onClick}>
      {text}
    </button>
    <hr />
  </div>
);

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

const ExternalLink = ({ url, className }) => (
  <a href={url} target="_blank" className={className}><i className="fa fa-external-link"></i></a>
);

export { FullButton, Errors, ExternalLink };
