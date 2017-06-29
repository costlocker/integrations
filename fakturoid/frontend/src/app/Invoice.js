import React from 'react';
import Loading from '../ui/Loading';

export default function Invoice({ fakturoidSubjects, form }) {
  if (!fakturoidSubjects) {
    return <Loading title="Loading fakturoid clients" />;
  }
  return <form className="form" onSubmit={form.submit}>
    <div className="form-group">
      <label htmlFor="fakturoidSubject">Fakturoid subject</label>
      <select required
        className="form-control" name="fakturoidSubject" id="fakturoidSubject"
        value={form.get('subject')} onChange={form.set('subject')}
      >
        <option></option>
        {fakturoidSubjects.map(subject => (
          <option key={subject.id} value={subject.id}>{subject.name}</option>
        ))}
      </select>
    </div>
    <button type="submit" className="btn btn-primary btn-block">Create invoice</button>
  </form>;
}
