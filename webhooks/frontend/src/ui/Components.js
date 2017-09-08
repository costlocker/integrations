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
  <a href={url} target="_blank" rel="noopener noreferrer">
    <i className="fa fa-external-link"></i>
    <span className="sr-only">{url}</span>
  </a>
);

const Button = ({ title, route, params, action, className }) => {
  const onClick = action ? action : () => redirectToRoute(route, params);
  return <button onClick={onClick} className={className}>{title}</button>;
};

const Link = ({ title, route, params, action, className }) => {
  const onClick = (e) => {
    e.preventDefault();
    const handler = action ? action : (e) => redirectToRoute(route, params, e)
    return handler(e);
  };
  return <a href="" onClick={onClick} className={className}>{title}</a>;
};

const roundNumber = value => {
  const rounded = Math.round(value * 100) / 100;
  return isNaN(rounded) ? null : rounded;
};

const Number = ({ value }) => <span title={value}>{roundNumber(value)}</span>;

const Image = ({ src }) => <img src={src} alt="" className="img-responsive img-thumbnail" />

export { Errors, ExternalLink, Button, Link, roundNumber, Number, Image };
