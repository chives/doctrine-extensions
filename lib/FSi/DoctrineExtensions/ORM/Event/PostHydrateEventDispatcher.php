<?php

/**
 * (c) FSi sp. z o.o. <info@fsi.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FSi\DoctrineExtensions\ORM\Event;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\Event\LifecycleEventArgs;
use FSi\DoctrineExtensions\ORM\Events;

class PostHydrateEventDispatcher
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var ClassMetadataFactory
     */
    private $metadataFactory;

    /**
     * The query hints
     *
     * @var array
     */
    private $hints = [];

    /**
     * Entities enqueued for postHydrate dispatching
     *
     * @var array
     */
    private $entities = [];

    /**
     * Constructs a new dispatcher instance
     *
     * @param EntityManager $em
     * @param array $hints
     */
    public function __construct(EntityManager $em, array $hints = [])
    {
        $this->entityManager = $em;
        $this->metadataFactory = $em->getMetadataFactory();
        $this->eventManager = $this->entityManager->getEventManager();
        $this->hints = $hints;
    }

    /**
     * Dispatches postHydrate event for specified entity or enqueues it for later dispatching
     *
     * @param object $entity
     */
    public function dispatchPostHydrate($entity)
    {
        $className = get_class($entity);

        if (!$this->eventManager->hasListeners(Events::postHydrate)) {
            return;
        }

        if (isset($this->hints[Query::HINT_INTERNAL_ITERATION])) {
            $this->eventManager->dispatchEvent(Events::postHydrate, new LifecycleEventArgs($entity, $this->entityManager));
        } else {
            if (!isset($this->entities[$className])) {
                $this->entities[$className] = [];
            }

            $this->entities[$className][] = $entity;
        }
    }

    /**
     * Dispatches all enqueued postHydrate events
     */
    public function dispatchEnqueuedPostHydrateEvents()
    {
        foreach ($this->entities as $entities) {
            foreach ($entities as $entity) {
                $this->eventManager->dispatchEvent(Events::postHydrate, new LifecycleEventArgs($entity, $this->entityManager));
            }
        }
    }
}
