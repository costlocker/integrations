import React from 'react';

export default function NewSubject({ form }) {
  return <form className="form" onSubmit={form.submit}>
    <div className="form-group">
      <label htmlFor="name">Name</label>
      <input
        className="form-control" required id="name"
        value={form.get('name')} onChange={form.set('name')}
      />
    </div>
    <button type="submit" className="btn btn-primary btn-block">Create a new subject</button>
  </form>
}
