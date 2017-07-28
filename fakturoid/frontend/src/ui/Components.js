import React from 'react';
import { redirectToRoute } from '../Router';
import { appHost } from '../config';
import { fakturoidHost } from '../state';

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
    e.preventDefault();
    const handler = action ? action : (e) => redirectToRoute(route, params, e)
    return handler(e);
  };
  return <a href="" onClick={onClick} className={className}>{title}</a>;
};

const CostlockerLink = ({ title, path, className }) => <a href={`${appHost}${path}`} className={className}>{title}</a>

const FakturoidLink = ({ title, path, className }) =>
  <ExternalLink title={title} url={`https://app.fakturoid.cz/${fakturoidHost()}${path}`} className={className} />

const roundNumber = value => {
  const rounded = Math.round(value * 100) / 100;
  return isNaN(rounded) ? null : rounded;
};

const Number = ({ value }) => <span title={value}>{roundNumber(value)}</span>;

const Image = (props) => <img {...props} alt="" />

const ImageTooltip = ({ url }) =>
  <div className="image-tooltip">
    <i className="fa fa-question-circle" />
    <Image src={url} />
  </div>;

export { Errors, ExternalLink, Button, Link, CostlockerLink, FakturoidLink, roundNumber, Number, Image, ImageTooltip };
