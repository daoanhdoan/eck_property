<?php
namespace Drupal\eck_property\Controller;

use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Url;
use Drupal\entity_property\EntityPropertyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class EckPropertyController extends ControllerBase implements ContainerInjectionInterface {
  /**
   * @var EntityPropertyInterface
   */
  protected $entityProperty;
  /**
   * @var FieldTypePluginManagerInterface
   */
  protected $fieldTypePluginManager;

  /**
   *
   * @param EntityPropertyInterface $entityProperty
   */
  public function __construct(EntityPropertyInterface $entityProperty, FieldTypePluginManagerInterface $fieldTypePluginManager, FormBuilderInterface $formBuilder)
  {
    $this->entityProperty = $entityProperty;
    $this->fieldTypePluginManager = $fieldTypePluginManager;
    $this->formBuilder = $formBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('entity_property'),
      $container->get('plugin.manager.field.field_type'),
      $container->get('form_builder')
    );
  }
  /**
   * Returns an administrative overview of Imce Profiles.
   */
  public function listing($eck_entity_type, Request $request) {
    // Build properties list.
    $output['properties'] = [
      '#theme' => 'table',
      '#header' => ['Label', 'Name', 'Type', 'Display configurable', 'Operation(s)'],
      '#rows' => [],
    ];
    /** @var ImmutableConfig $config */
    $properties = \Drupal::config('entity_property.properties.' . $eck_entity_type->id());
    $rows = [];

    $entity_type = \Drupal::entityTypeManager()->getDefinition($eck_entity_type->id());
    $class = $entity_type->getClass();
    /** @var FieldDefinitionInterface[] $fieldDefinitions */
    $fieldDefinitions = $class::baseFieldDefinitions($entity_type);
    foreach ($fieldDefinitions as $id => $field) {
      if($properties->get($id)) {
        continue;
      }
      $row = [
        ['data' => $field->getLabel()],
        ['data' => $id]
      ];
      $field_type = $this->fieldTypePluginManager->getDefinition($field->getType());
      $row[] = ['data' => $field_type['label']];
      $row[] = ['data' => ""];
      $row[] = [
        'data' => []
      ];
      $rows[] = $row;
    }
    foreach ($properties->getRawData() as $id => $property) {
      $row = [
        ['data' => $property['label']],
        ['data' => $property['name']]
      ];
      /** @var FieldDefinitionInterface $field_type */
      $field_type = $this->fieldTypePluginManager->getDefinition($property['type']);
      $row[] = ['data' => $field_type['label']];

      $configurable = array_map(function ($var) {
        return ucfirst($var);
      }, array_filter(array_values($property['configurable'])));
      $row[] = ['data' => implode(", ", $configurable)];

      $links = [];
      if (!$this->entityProperty->hasData($eck_entity_type->id(), $id)) {
        $link_args = ['entity_type' => $eck_entity_type->id(), 'property_name' => $property['name']];
        $links['edit'] = [
          'title' => $this->t('Edit'),
          'url' => Url::fromRoute('entity_property.entity_property_edit', $link_args, ['query' => \Drupal::destination()->getAsArray()]),
        ];

        $links['delete'] = [
          'title' => $this->t('Delete'),
          'url' => Url::fromRoute('entity_property.entity_property_delete', $link_args, ['query' => \Drupal::destination()->getAsArray()]),
        ];
      }

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      $rows[] = $row;
    }
    $output['properties']['#rows'] = $rows;
    return $output;
  }

  /**
   * Returns an administrative overview of Imce Profiles.
   */
  public function add($eck_entity_type, Request $request) {
    return $this->formBuilder->getForm('\Drupal\entity_property\Form\EntityPropertyForm', $eck_entity_type->id());
  }

  /**
   * Returns an administrative overview of Imce Profiles.
   */
  public function addBulk($eck_entity_type, Request $request) {
    return $this->formBuilder->getForm('\Drupal\entity_property\Form\EntityPropertiesForm', $eck_entity_type->id());
  }
}
