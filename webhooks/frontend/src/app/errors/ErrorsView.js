import React from 'react';

export default function ErrorsView({ errors }) {
  if (errors.hasNoErrors()) {
    return null;
  }
  return <div className="row">
    <div className="col-sm-12">
      <div className="alert alert-danger">
        <strong>Please fix following errors:</strong><br />
        <pre><code>{JSON.stringify(errors.getAll(), null, 2)}</code></pre>
      </div>
    </div>
  </div>;
}
