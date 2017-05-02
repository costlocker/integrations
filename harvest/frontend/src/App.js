import React from 'react';

export default function () {
  return (
    <div className="row">
      <div className="col-sm-6 col-sm-offset-3">
        <form>
          <h2>Harvest</h2>
          <div className="form-group">
            <label htmlFor="username">Username</label>
            <input type="email" className="form-control" id="username" placeholder="Username" />
          </div>
          <div className="form-group">
            <label htmlFor="password">Password</label>
            <input type="password" className="form-control" id="username" placeholder="Password" />
          </div>
          <div className="form-group">
            <label htmlFor="harvest">Harvest domain</label>
            <div className="input-group">
              <input type="text" className="form-control" placeholder="mycompany" id="harvest" />
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
