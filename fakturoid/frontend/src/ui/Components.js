import React from 'react';
import { redirectToRoute } from '../Router';
import { appHost } from '../config';
import { fakturoidHost } from '../state';

const Errors = ({ title, error, errorClassName }) => {
  if (!error) {
    return null;
  }
  const decodedUrlError = decodeURIComponent(error).replace(/%20/g, ' '); // hotfix for '%2520' spaces
  return (
    <div className={`alert alert-${errorClassName || 'danger'}`}>
      <strong>{title}</strong><br />
      {decodedUrlError}
    </div>
  );
};

const ExternalLink = ({ url, title, className }) => (
  <a href={url} className={className} target="_blank" rel="noopener noreferrer">
    {title ? title : <span><i className="fa fa-external-link"></i><span className="sr-only">{url}</span></span>}
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

class FormElementWithoutJumpingCursor extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      isFocused: false,
      currentValue: props.value,
    };
  }
  handleChange = (e) => {
    this.setState({ currentValue: e.target.value });
    this.props.onChange(e);
  }
  handleFocus = (e) => {
    this.setState({ isFocused: true });
  }
  handleBlur = (e) => {
    this.setState({ isFocused: false });
  }
  componentWillReceiveProps(nextProps) {
    if (!this.state.isFocused){
      this.setState({ currentValue: nextProps.value });
    }
  }
  render() {
    const Element = this.props.type ? 'input' : 'textarea';
    return <Element
      {...this.props}
      onChange={this.handleChange}
      onFocus={this.handleFocus}
      onBlur={this.handleBlur}
      value={this.state.currentValue}
    />;
  }
};

const Input = (props) => props.onChange ? <FormElementWithoutJumpingCursor {...props} /> : <input {...props} />;
const Textarea = (props) => <FormElementWithoutJumpingCursor {...props} />;

export { Errors, ExternalLink, Button, Link, CostlockerLink, FakturoidLink, roundNumber, Number, RadioButtons, Input, Textarea };
