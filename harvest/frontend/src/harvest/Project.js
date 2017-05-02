import React from 'react';

export default function Project({ project }) {
  return (
    <ul>
      <li>ID: <strong>{project.id}</strong></li>
      <li>Name: <strong>{project.name}</strong></li>
    </ul>
  );
};
