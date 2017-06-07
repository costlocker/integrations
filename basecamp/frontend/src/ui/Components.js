import React from 'react';
import { redirectToRoute } from '../Router';

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

const Button = ({ title, route, params, action, className }) => {
  const onClick = action ? action : () => redirectToRoute(route, params);
  return <button onClick={onClick} className={className}>{title}</button>;
};

const Link = ({ title, route }) => (
  <a href="" onClick={(e) => redirectToRoute(route, undefined, e)}>{title}</a>
);

export { Errors, ExternalLink, Button, Link };
