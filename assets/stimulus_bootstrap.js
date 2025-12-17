import { Application } from '@hotwired/stimulus';

const app = Application.start();

// Dynamically load all controllers from the controllers directory
const context = require.context('./controllers', true, /\.js$/);
context.keys().forEach((key) => {
    const fileName = key.replace('./', '');
    // Ignore files that don't match *_controller.js pattern if necessary, 
    // but usually all files in this dir are controllers or helpers.
    // Let's assume standard naming: name_controller.js
    
    if (!fileName.endsWith('_controller.js')) return;

    const controllerName = fileName
        .replace('_controller.js', '')
        .replace(/_/g, '-')
        .replace(/\//g, '--');
        
    app.register(controllerName, context(key).default);
});
