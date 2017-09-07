
import { Set } from 'immutable';

const defaultSyncSettings = {
    // costlocker -> basecamp
    areTodosEnabled: true,
    isDeletingTodosEnabled: false,
    isRevokeAccessEnabled: false,
    // basecamp -> costlocker
    areTasksEnabled: false,
    isDeletingTasksEnabled: false,
    isCreatingActivitiesEnabled: false,
    isDeletingActivitiesEnabled: false,
    isBasecampWebhookEnabled: false,
};

class SyncSettings {
  constructor(appState) {
    this.appState = appState;
    this.isExistingProjectEdited = false;
  }

  setProjectId(idProject) {
    this.idProject = idProject;
  }

  loadProjectSettings(allProjects) {
    const projects = allProjects ? allProjects.filter(p => p.id == this.idProject) : [];
    if (!projects.length) {
      return this.loadCompanySettings();
    }

    const editedProject = projects[0];
    this.isExistingProjectEdited = editedProject.basecamps.length > 0;
    if (!this.isExistingProjectEdited) {
      return this.loadCompanySettings(editedProject.id);
    }

    const basecampProject = editedProject.basecamps[0];
    this.appState.cursor(['sync']).update(sync => sync
      .set('mode', 'edit')
      .set('costlockerProject', Set([editedProject.id]))
      .set('basecampProject', basecampProject.id)
      .set('account', basecampProject.account.id)
      .set('areTodosEnabled', basecampProject.settings.areTodosEnabled)
      .set('isDeletingTodosEnabled', basecampProject.settings.isDeletingTodosEnabled)
      .set('isRevokeAccessEnabled', basecampProject.settings.isRevokeAccessEnabled)
      .set('areTasksEnabled', basecampProject.settings.areTasksEnabled)
      .set('isDeletingTasksEnabled', basecampProject.settings.isDeletingTasksEnabled)
      .set('isCreatingActivitiesEnabled', basecampProject.settings.isCreatingActivitiesEnabled)
      .set('isDeletingActivitiesEnabled', basecampProject.settings.isDeletingActivitiesEnabled)
      .set('isBasecampWebhookEnabled', basecampProject.settings.isBasecampWebhookEnabled)
    )
  }

  loadCompanySettings(preselectedProject) {
    const companySettings = this.appState.cursor(['companySettings']).deref();
    const myAccount = this.appState.cursor(['auth', 'settings']).myAccount;
    this.appState.cursor(['sync']).update(sync => sync
      .set('mode', 'create')
      .set('costlockerProject', Set(preselectedProject ? [preselectedProject] : []))
      .set('basecampProject', '')
      .set('account', myAccount ? myAccount : sync.get('account'))
      .set('areTodosEnabled', companySettings.get('areTodosEnabled'))
      .set('isDeletingTodosEnabled', companySettings.get('isDeletingTodosEnabled'))
      .set('isRevokeAccessEnabled', companySettings.get('isRevokeAccessEnabled'))
      .set('areTasksEnabled', companySettings.get('areTasksEnabled'))
      .set('isDeletingTasksEnabled', companySettings.get('isDeletingTasksEnabled'))
      .set('isCreatingActivitiesEnabled', companySettings.get('isCreatingActivitiesEnabled'))
      .set('isDeletingActivitiesEnabled', companySettings.get('isDeletingActivitiesEnabled'))
      .set('isBasecampWebhookEnabled', companySettings.get('isBasecampWebhookEnabled'))
    );
    this.isExistingProjectEdited = false;
  }
}

export { SyncSettings, defaultSyncSettings }
