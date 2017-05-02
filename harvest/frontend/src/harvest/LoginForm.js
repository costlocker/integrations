import React, { Component } from 'react';

export default class extends Component {
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
