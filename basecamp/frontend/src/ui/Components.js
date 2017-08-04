import React from 'react';
import { redirectToRoute } from '../Router';

const Errors = ({ title, error }) => {
  if (!error) {
    return null;
  }
  const decodedUrlError = decodeURIComponent(error).replace(/%20/g, ' '); // hotfix for '%2520' spaces
  return (
    <div className="alert alert-danger">
      <strong>{title}</strong><br />
      {decodedUrlError}
    </div>
  );
};

const ExternalLink = ({ url }) => (
  <a href={url} target="_blank" rel="noopener noreferrer"><i className="fa fa-external-link"></i></a>
);

const Button = ({ title, route, params, action, className }) => {
  const onClick = action ? action : () => redirectToRoute(route, params);
  return <button onClick={onClick} className={className}>{title}</button>;
};

const Link = ({ title, route, params }) => (
  <a href="" onClick={(e) => redirectToRoute(route, params, e)}>{title}</a>
);

export { Errors, ExternalLink, Button, Link };
