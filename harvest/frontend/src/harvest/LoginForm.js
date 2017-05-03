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
    let user = null;
    if (this.props.harvestUser) {
        user = <button className="btn btn-success" onClick={this.props.goToNextStep}>
          Continue as <strong>{this.props.harvestUser.user_name}</strong>
        </button>
    }
    return (
      <div>
        <div className="row">
          <div className="col-sm-6">
            {user}
          </div>
        </div>
        <div className="row">
          <div className="col-sm-6">
            <form onSubmit={this.handleSubmit}>
              <h2>Change Harvest Account</h2>
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
              <div>
                <button className="btn btn-primary">Login to Harvest</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    );
  }
}
