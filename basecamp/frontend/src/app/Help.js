import React from 'react';

import { Link } from '../ui/Components';

export default function Help() {
  return (
    <div>
      <div className="row">
        <div className="col-sm-12">
          <h1>Help</h1>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h2>How does the integration with Basecamp work?</h2>
          <p>
            The first step is connecting your Costlocker account to the Basecamp one. Once you’re connected,
            choose a Project you want to connect to Basecamp, and Costlocker exports the Cost Estimate of
            your Projects from Costlocker to either an existing or a new project in Basecamp.
          </p>
          <p>
            To be more specific, the Activity in the Cost Estimate (e.g. Graphic Design) becomes a to-do list
            in Basecamp. And the to-do items in this list (e.g. Homepage Design) are the Task you've added
            to the Cost Estimate in Costlocker. If a person doesn't have a specific Task in Costlocker,
            then his to-do item will be the name of the Activity. You can connect (and disconnect) a
            Project to Basecamp in the Basecamp tab of each Project.
          </p>
          <p>
            The brilliant thing about it is that every to-do item in Basecamp has its person assigned to
            that – the person who’s supposed to work on that to-do – so they receive a Basecamp
            invitation straight away.
          </p>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <h2>New features</h2>
          <p>
            We've reworked the built-in Basecamp integration and improved it based on your feedback:
          </p>
          <ul className="bullet">
            <li>two-way synchronization for Basecamp3 projects (e.g. create a task in Costlocker when a new todo is created in Basecamp)</li>
            <li>automatically create a Basecamp project after creating the project in Costlocker (allow it in <Link route='settings' title='settings' />)</li>
            <li><Link route='sync' title='Import' /> multiple projects to Basecamp at once</li>
            <li>right order of todolists when project is synchronized for the first time (order is reversed in the old integration)</li>
            <li>create a project in Basecamp without creating todolists</li>
            <li>overview of connected <Link route='projects' title='projects' />, <Link route='accounts' title='accounts' /> and <Link route='events' title='events' /> (changelog what happened...)</li>
            <li>global <Link route='settings' title='synchronization settings' /> usable as a template for new projects</li>
          </ul>
        </div>
      </div>
      <div className="row">
        <div className="col-sm-12">
          <img
             src="https://user-images.githubusercontent.com/7994022/27282066-bc4eac76-54ee-11e7-8aec-8fc756f83f31.png"
             alt="Interkom" className="pull-right img-thumbnail" width="250px" />
          <h2>Migrating from built-in integration</h2>
          <p>
            Get in touch with us. We'll import selected projects. If you are satisfied with the new version,
            then we'll import rest of your projects.
          </p>
          <h2>Contact</h2>
          <p>
            Please use your interkom account at <a href="http://new.costlocker.com/">new.costlocker.com</a>.<br />
            Or write us at <a href="mailto:support@costlocker.com">support@costlocker.com</a>.
          </p>
        </div>
      </div>
    </div>
  );
};
