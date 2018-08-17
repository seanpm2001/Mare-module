<?php
namespace Mare;

use Omeka\Api\Representation\ItemRepresentation;
use Omeka\Module\AbstractModule;
use Zend\EventManager\Event;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Module extends AbstractModule
{
    public function install(ServiceLocatorInterface $services)
    {
        $importer = $services->get('Omeka\RdfImporter');
        $conn = $services->get('Omeka\Connection');
        $api = $services->get('Omeka\ApiManager');

        // Import the MARE vocabulary.
        $importer->import(
            'file',
            [
                'o:namespace_uri' => 'http://religiousecologies.org/vocab#',
                'o:prefix' => 'mare',
                'o:label' => 'Mapping American Religious Ecologies',
                'o:comment' =>  null,
            ],
            [
                'file' => __DIR__ . '/vocabs/mare.n3',
                'format' => 'turtle',
            ]
        );

        // Get vocabulary members (classes and properties).
        $vocabMembers = [];
        foreach (['resource_class', 'property'] as $member) {
            $sql = 'SELECT m.id, m.local_name, v.prefix FROM %s m JOIN vocabulary v ON m.vocabulary_id = v.id';
            $stmt = $conn->query(sprintf($sql, $member));
            $vocabMembers[$member] = [];
            foreach ($stmt as $row) {
                $vocabMembers[$member][sprintf('%s:%s', $row['prefix'], $row['local_name'])] = $row['id'];
            }
        }

        // Create the MARE item sets.
        $response = $api->batchCreate('item_sets', [
            [
                'dcterms:title' => [
                    [
                        'type' => 'literal',
                        'property_id' => $vocabMembers['property']['dcterms:title'],
                        '@value' => 'Schedules',
                    ],
                ],
            ],
            [
                'dcterms:title' => [
                    [
                        'type' => 'literal',
                        'property_id' => $vocabMembers['property']['dcterms:title'],
                        '@value' => 'Denominations',
                    ],
                ],
            ],
            [
                'dcterms:title' => [
                    [
                        'type' => 'literal',
                        'property_id' => $vocabMembers['property']['dcterms:title'],
                        '@value' => 'Counties',
                    ],
                ],
            ],
        ]);

        // Create the MARE resource templates.
        $response = $api->batchCreate('resource_templates', [
            [
                'o:label' => 'Schedule',
                'o:resource_class' => ['o:id' => $vocabMembers['resource_class']['mare:Schedule']],
                'o:resource_template_property' => [
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:title']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:creator']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:created']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:source']],
                        'o:data_type' => 'uri',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:box']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:scheduleId']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:denomination']],
                        'o:data_type' => 'resource:item',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:denominationId']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:county']],
                        'o:data_type' => 'resource:item',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:countyId']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:digitized']],
                        'o:data_type' => 'literal',
                    ],
                ],
            ],
            [
                'o:label' => 'Denomination',
                'o:resource_class' => ['o:id' => $vocabMembers['resource_class']['mare:Denomination']],
                'o:resource_template_property' => [
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:title']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:description']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:denominationId']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:denominationFamily']],
                        'o:data_type' => 'literal',
                    ],
                ]
            ],
            [
                'o:label' => 'County',
                'o:resource_class' => ['o:id' => $vocabMembers['resource_class']['mare:County']],
                'o:resource_template_property' => [
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:title']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:countyId']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:fips']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['mare:stateTerritory']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:type']],
                        'o:data_type' => 'literal',
                    ],
                    [
                        'o:property' => ['o:id' => $vocabMembers['property']['dcterms:source']],
                        'o:data_type' => 'uri',
                    ],
                ]
            ],
        ]);
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        // Add section navigation to items.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.section_nav',
            function (Event $event) {
                $view = $event->getTarget();
                $item = $view->item;
                if ($this->isClass('mare:Schedule', $item)) {
                    $sectionNav = $event->getParam('section_nav');
                    $sectionNav['mare-schedule-transcribe'] = 'Transcribe';
                    //~ $event->setParam('section_nav', $sectionNav);
                }
            }
        );
        // Add section content to items.
        $sharedEventManager->attach(
            'Omeka\Controller\Admin\Item',
            'view.show.after',
            function (Event $event) {
                $view = $event->getTarget();
                $item = $view->item;
                if ($this->isClass('mare:Schedule', $item)) {
                    //~ echo $view->partial('religious-ecologies/transcribe', []);
                }
            }
        );
    }

    /**
     * Check whether the passed item is an instance of the passed class.
     *
     * @param string $className
     * @param ItemRepresentation $item
     * @return bool
     */
    public function isClass($className, ItemRepresentation $item)
    {
        $class = $item->resourceClass();
        if (!$class) {
            return false;
        }
        if ($className !== $class->term()) {
            return false;
        }
        return true;
    }
}
