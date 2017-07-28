import React from 'react';

import { Errors, ExternalLink, FakturoidLink } from '../ui/Components';
import { ImageTooltip } from '../ui/Images';
import slug from '../images/slug.png';
import token from '../images/token.png';

export default function Login({ costlockerAuth, fakturoidAuth, isLoggedInFakturoid, loginUrls, loginError, form }) {
  const currentSlug = form.get('slug');
  const fakturoidUrl = `https://app.fakturoid.cz/${currentSlug}`;
  return (
    <div>
      <div className="row text-center">
        <div className="col-sm-12">
          <Errors title="Login error" error={loginError} />
        </div>
      </div>
      <div className="row">
        <div className="col-sm-6 text-center">
          <h2>Costlocker <ExternalLink url="https://new.costlocker.com" /></h2>
          <a href={loginUrls.costlocker} className="btn btn-primary">
            {costlockerAuth
              ? <span>Switch account <strong>{costlockerAuth.company.name}</strong></span>
              : 'Login to Costlocker'}
          </a>
        </div>
        <div className="col-sm-6">
          <h2>Fakturoid <ExternalLink url={fakturoidUrl} /></h2>
          {costlockerAuth ? (
            <form action={loginUrls.fakturoid} method="POST">
              <div className="form-group">
                <label htmlFor="email">Email address</label>
                <input type="email" className="form-control" id="email" name="email" placeholder="Email"
                  defaultValue={fakturoidAuth ? fakturoidAuth.person.email : null} />
              </div>
              <div className="form-group">
                <label htmlFor="token">
                  Fakturoid slug (subdomain) <ImageTooltip url={slug} />
                </label>
                <input type="text" className="form-control" id="slug" name="slug" placeholder="YOUR_SLUG"
                  value={form.get('slug')} onChange={form.set('slug')} />
              </div>
              <div className="form-group">
                <label htmlFor="token">
                  API token <ImageTooltip url={token} />
                </label>
                <input type="text" className="form-control" id="token" name="token" />
                <p className="help-block">
                  <FakturoidLink path="/user" title="Find your API token here" />
                </p>
              </div>
              <button type="submit" className="btn btn-primary btn-block">
                {isLoggedInFakturoid ? <span>Switch account <strong>{currentSlug}</strong></span> : 'Login'}
              </button>
            </form>
          ) : (
              <p className="text-muted">At first you have to login to Costlocker</p>
            )}
        </div>
      </div>
    </div>
  );
};
