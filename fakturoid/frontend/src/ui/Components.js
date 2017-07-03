import React from 'react';
import { redirectToRoute } from '../Router';
import { appHost } from '../config';

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
  const onClick = (e) => {
    e.preventDefault();
    const handler = action ? action : (e) => redirectToRoute(route, params, e)
    return handler(e);
  };
  return <a href="" onClick={onClick} className={className}>{title}</a>;
};

const CostlockerLink = ({ title, path }) => <a href={`${appHost}${path}`}>{title}</a>

const roundNumber = value => {
  const rounded = Math.round(value * 100) / 100;
  return isNaN(rounded) ? null : rounded;
};

const Number = ({ value }) => <span title={value}>{roundNumber(value)}</span>;

export { Errors, ExternalLink, Button, Link, CostlockerLink, roundNumber, Number };
