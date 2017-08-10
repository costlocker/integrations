import React from 'react';
import { redirectToRoute, generateUrl } from '../Router';
import { appHost } from '../config';

const Errors = ({ title, error, errorClassName }) => {
  if (!error) {
    return null;
  }
  const decodedUrlError = typeof(error) === 'string'
    ? decodeURIComponent(error).replace(/%20/g, ' ') // hotfix for '%2520' spaces
    : error;
  return (
    <div className={`alert alert-${errorClassName || 'danger'}`}>
      <strong>{title}</strong><br />
      {decodedUrlError}
    </div>
  );
};

const ExternalLink = ({ url, title, className }) => (
  <a href={url} className={className} target="_blank" rel="noopener noreferrer">
    {title ? title : <i className="fa fa-external-link"></i>}
  </a>
);

const Button = ({ title, route, params, action, className }) => {
  const onClick = action ? action : () => redirectToRoute(route, params);
  return <button onClick={onClick} className={className}>{title}</button>;
};

const Link = ({ title, route, params, action, className }) => {
  const onClick = (e) => {
    if (action) {
      e.preventDefault();
      return action();
    }
    return redirectToRoute(route, params, e);
  };
  return <a href={action ? '#action' : generateUrl(route, params)} onClick={onClick} className={className}>{title}</a>;
};

const CostlockerLink = ({ title, path, className }) => <a href={`${appHost}${path}`} className={className}>{title}</a>

const RadioButtons = ({ items, isActive, onChange, className }) =>
  <div className={`btn-group ${className}`}>
    {items.map(type => (
      <label key={type.id} className={isActive(type) ? 'btn btn-primary active' : 'btn btn-default'}>
        <input
          type="radio" name="type" value={type.id} className="hide"
          checked={isActive(type)} onChange={onChange} /> {type.title}
      </label>
    ))}
  </div>;

export { Errors, ExternalLink, Button, Link, CostlockerLink, RadioButtons };
