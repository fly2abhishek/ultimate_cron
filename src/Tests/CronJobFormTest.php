<?php
/**
 * @file
 * Contains \Drupal\ultimate_cron\Tests\CronJobFormTest.
 */

namespace Drupal\ultimate_cron\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\ultimate_cron\Entity\CronJob;

/**
 * Cron Job Form Testing
 *
 * @group ultimate_cron
 */
class CronJobFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'ultimate_cron',
    'block',
    'ultimate_cron_plugin_test',
  ];

  /**
   * A user with permission to create and edit books and to administer blocks.
   *
   * @var object
   */
  protected $admin_user;

  /**
   * Cron job name.
   *
   * @var string
   */
  protected $job_name;

  /**
   * Cron job machine id.
   *
   * @var string
   */
  protected $job_id = 'system_cron';

  /**
   * Tests adding and editing a cron job.
   */
  function testManageJob() {
    $this->drupalPlaceBlock('local_tasks_block');
    $this->drupalPlaceBlock('local_actions_block');

    // Create user with correct permission.
    $this->admin_user = $this->drupalCreateUser(array('administer ultimate cron', 'administer site configuration'));
    $this->drupalLogin($this->admin_user);

    // Cron Jobs overview.
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertResponse('200');

    // Check for the default schedule message in Job list.
    $this->assertText('Every 15 min');
    // Check for the Last Run default value.
    $this->assertText('Never');

    // Start editing added job.
    $this->drupalGet('admin/config/system/cron/jobs/manage/' . $this->job_id);
    $this->assertResponse('200');

    print_r($this->job_name);
    // Set new cron job configuration and save the old job name.
    $job = CronJob::load($this->job_id);
    $old_job_name = $job->label();
    $this->job_name = 'edited job name';
    $edit = array('title' => $this->job_name);

    // Save the new job.
    $this->drupalPostForm(NULL, $edit, t('Save'));
    // Assert the edited Job hasn't run yet.
    $this->assertText('Never');
    // Assert drupal_set_message for successful updated job.
    $this->assertText(t('job @name has been updated.', array('@name' => $this->job_name)));

    // Run the Jobs.
    $this->cronRun();

    // Assert the cron jobs have been run by checking the time.
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertText(\Drupal::service('date.formatter')->format(\Drupal::state()->get('system.cron_last'), 'short'), 'Created Cron jobs have been run.');

    // Check that all jobs have been run.
    $this->assertNoText("Never");

    // Assert cron job overview for recently updated job.
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertNoText($old_job_name);
    $this->assertText($this->job_name);

    // Change time when cron runs, check the 'Scheduled' label is updated.
    $this->clickLink(t('Edit'));
    $this->drupalPostForm(NULL, ['scheduler[rules][0]' => '0+@ */6 * * *'], t('Save'));
    $this->assertText('Every 6 hours');

    // Test disabling a job.
    $this->clickLink(t('Disable'), 0);
    $this->assertText('This cron job will no longer be executed.');
    $this->drupalPostForm(NULL, NULL, t('Disable'));

    // Assert drupal_set_message for successful disabled job.
    $this->assertText(t('Disabled cron job @name.', array('@name' => $this->job_name)));
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[6]', 'Disabled');
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Enable');
    $this->assertNoFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Run');

    // Test enabling a job.
    $this->clickLink(t('Enable'), 0);
    $this->assertText('This cron job will be executed again.');
    $this->drupalPostForm(NULL, NULL, t('Enable'));

    // Assert drupal_set_message for successful enabled job.
    $this->assertText(t('Enabled cron job @name.', array('@name' => $this->job_name)));
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertTrue(strpos($this->xpath('//table/tbody/tr[1]/td[6]/img')[0]->asXml(), 'core/misc/icons/73b355/check.svg'));
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Run');

    // Test disabling a job with the checkbox on the edit page.
    $edit = array(
      'status' => FALSE,
    );
    $this->drupalPostForm('admin/config/system/cron/jobs/manage/' . $this->job_id, $edit, t('Save'));
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[6]', 'Disabled');
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Enable');
    $this->assertNoFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Run');

    // Test enabling a job with the checkbox on the edit page.
    $edit = array(
      'status' => TRUE,
    );
    $this->drupalPostForm('admin/config/system/cron/jobs/manage/' . $this->job_id, $edit, t('Save'));
    $this->assertTrue(strpos($this->xpath('//table/tbody/tr[1]/td[6]/img')[0]->asXml(), 'core/misc/icons/73b355/check.svg'));
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li[1]/a', 'Run');

    $this->drupalGet('admin/config/system/cron/jobs');

    // Save new job.
    $this->clickLink(t('Edit'), 0);
    $job_configuration = array(
      'scheduler[id]' => 'crontab',
    );
    $this->drupalPostForm(NULL, $job_configuration, t('Save'));
    $this->drupalPostForm('admin/config/system/cron/jobs/manage/' . $this->job_id, ['scheduler[rules][0]' => '0+@ * * * *'], t('Save'));
    $this->assertText('0+@ * * * *');

    // Try editing the rule to an invalid one.
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->clickLink('Edit');
    $this->drupalPostForm(NULL, ['scheduler[rules][0]' => '*//15+@ *-2 * * *'], t('Save'));
    $this->assertText('Rule is invalid');
    $this->assertTitle('Edit job | Drupal');

    // Assert that there is no Delete link on the details page.
    $this->assertNoLink('Delete');

    // Force a job to be invalid by changing the callback.
    $job = CronJob::load($this->job_id);
    $job->setCallback('non_existing_function')
      ->save();
    $this->drupalGet('admin/config/system/cron/jobs');

    // Assert that the invalid cron job is displayed properly.
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[6]', 'Missing');
    $this->assertFieldByXPath('//table/tbody/tr[1]/td[7]/div/div/ul/li/a', 'Delete');

    // Test deleting a job (only possible if invalid cron job).
    $this->clickLink(t('Delete'), 0);
    $this->drupalPostForm(NULL, NULL, t('Delete'));
    $this->assertText(t('The cron job @name has been deleted.', array('@name' => $job->label())));
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->assertNoText($job->label());

    // Test logs details page.
    $this->drupalGet('admin/config/system/cron/jobs');
    $user = $this->admin_user->getAccountName() . ' (' . $this->admin_user->id() . ')';
    $this->clickLink('Logs');
    $xpath = $this->xpath('//tbody/tr[@class="odd"]');
    $parameter_date = \Drupal::service('date.formatter')->format(\Drupal::state()->get('system.cron_last'), 'custom', 'Y-m-d H:i:s');
    $this->assertEqual((string) $xpath[0]->td[1], $user);
    $this->assertEqual((string) $xpath[0]->td[2], $parameter_date);
    $this->assertEqual((string) $xpath[0]->td[3], $parameter_date);
    $this->assertEqual((string) $xpath[0]->td[4], 'Launched manually by ' . $user);
    $this->assertEqual((string) $xpath[0]->td[5], '00:00');

    // Test that the plugin from ultimate_cron_plugin_test is available
    $this->drupalGet('admin/config/system/cron/jobs');
    $this->clickLink(t('Edit'), 0);
    $job_configuration = array(
      'scheduler[id]' => 'simpler',
    );
    $this->drupalPostForm(NULL, $job_configuration, t('Select'));
    $this->assertText('Simpler Run cron every', 'New plugin label is found.');
    $this->assertOption('edit-scheduler-rules-0', '* * * * *');
    $this->assertNoOption('edit-scheduler-rules-0', '0+@ 0 * * 0', 'Option available in Simpler plugin is not shown.');
    $job_configuration['scheduler[rules][0]'] = '* * * * *';
    $this->drupalPostForm(NULL, $job_configuration, t('Save'));
    $this->assertText('Simpler Every 1 min', 'Plugin label override used');

  }

}
