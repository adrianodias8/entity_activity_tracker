<?php

namespace Drupal\entity_activity_tracker\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Driver\mysql\Connection;
use PDO;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Class ExportActivityRecordsController.
 */
class ExportActivityRecordsController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * The filesystem settings.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $filesystemSettigns;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = new static();
    $instance->database = $container->get('database');
    $instance->filesystemSettigns = $container->get('config.factory')->get('system.file');
    return $instance;
  }

  /**
   * Export.
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Return Activity export in CSV.
   */
  public function export() {

    // Get everything from activity table;.
    $query = $this->database->select('entity_activity_tracker', 'fa')
      ->fields('fa');

    if ($first_row = $query->execute()->fetchAssoc()) {

      $temp_dir = $this->filesystemSettigns->get('path.temporary');
      $temp_file = tempnam($temp_dir, 'activity_csv_export_');

      // Export to .CSV.
      $fp = fopen($temp_file, 'w');

      $headers = array_keys((array) $first_row);
      // Put the headers.
      fputcsv($fp, $headers);

      $rows = $query->execute()->fetchAllAssoc('activity_id', PDO::FETCH_ASSOC);
      foreach ($rows as $row) {
        // Push the rest.
        fputcsv($fp, array_values((array) $row));
      }

      fclose($fp);
      $file_content = file_get_contents($temp_file);
      unlink($temp_file);

      $time = date('d_m_Y');
      $filename = "activity_export_{$time}.csv";

      $response = new Response($file_content);
      $content_disposition = $response->headers->makeDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition', $content_disposition);

      return $response;

    }

    return [
      '#type' => 'markup',
      '#markup' => $this->t('Nothing to export.'),
    ];
  }

}
