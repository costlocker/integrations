import React, { Component } from 'react';

const HarvestUser = ({ harvestUser }) => {
  return (
    <ul>
      <li><img src={harvestUser.get('user_avatar')} alt="" /></li>
      <li>User: <strong>{harvestUser.get('user_name')}</strong></li>
      <li>Company: <a href={harvestUser.get('company_url')}>{harvestUser.get('company_name')}</a></li>
    </ul>
  );
};

class HarvestLoginForm extends Component {
  handleSubmit = (e) => {
      e.preventDefault();
      const formData = {};
      for (const field in this.refs) {
        formData[field] = this.refs[field].value;
      }
      this.props.handleHarvestLogin(formData);
  }
  render() {
    return (
      <div className="row">
        <div className="col-sm-6 col-sm-offset-3">
          <form onSubmit={this.handleSubmit}>
            <h2>Harvest</h2>
            <div className="form-group">
              <label htmlFor="username">Username</label>
              <input ref="username" type="email" className="form-control" id="username" placeholder="Username" />
            </div>
            <div className="form-group">
              <label htmlFor="password">Password</label>
              <input ref="password" type="password" className="form-control" id="username" placeholder="Password" />
            </div>
            <div className="form-group">
              <label htmlFor="domain">Harvest domain</label>
              <div className="input-group">
                <input ref="domain" type="text" className="form-control" placeholder="mycompany" id="domain" />
                <span className="input-group-addon">.harvestapp.com</span>
              </div>
            </div>
            <div className="text-center">
              <button className="btn btn-primary">Login to Harvest</button>
            </div>
          </form>
        </div>
      </div>
    );
  }
}

export default function ({ harvestUser, handleHarvestLogin }) {
  const user = harvestUser.deref();
  if (user.get('company_name')) {
    return <HarvestUser harvestUser={user} />
  }
  return <HarvestLoginForm handleHarvestLogin={handleHarvestLogin} />
}
