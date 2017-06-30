import React from 'react';
import { redirectToRoute } from '../Router';

const Errors = ({ title, error }) => {
  if (!error) {
    return null;
  }
  return (
    <div className="alert alert-danger">
      <strong>{title}</strong><br />
      {decodeURIComponent(error)}
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

const Link = ({ title, route, params, action, className }) => {
  const onClick = action ? action : (e) => redirectToRoute(route, params, e);
  return <a href="" onClick={onClick} className={className}>{title}</a>;
};

export { Errors, ExternalLink, Button, Link };
