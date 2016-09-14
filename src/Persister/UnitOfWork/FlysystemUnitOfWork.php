<?php

namespace Kiboko\Component\BatchORM\Persister\UnitOfWork;

use League\Flysystem\File;
use Symfony\Component\Serializer\SerializerInterface;

class FlysystemUnitOfWork implements UnitOfWorkInterface
{
    /**
     * @var File
     */
    private $file;

    /**
     * @var \SplQueue
     */
    private $queue;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var bool
     */
    private $firstLineIgnored;

    /**
     * CSVUnitOfWork constructor.
     *
     * @param File $file
     * @param SerializerInterface $serializer
     * @param bool $firstLineIgnored
     */
    public function __construct(
        File $file,
        SerializerInterface $serializer,
        $firstLineIgnored = false
    ) {
        $this->file = $file;
        $this->queue = new \SplQueue();
        $this->serializer = $serializer;
        $this->firstLineIgnored = $firstLineIgnored;
    }

    /**
     * @return \Traversable|object[]
     */
    public function getIterator()
    {
        foreach ($this->queue as $item) {
            yield $item;
        }
    }

    /**
     * @param object $value
     */
    public function enqueue($value)
    {
        $this->queue->enqueue($value);
        $this->file->write($this->serializer->serialize($value, 'csv'));
    }

    /**
     * @return bool
     */
    public function isFirstLineIgnored()
    {
        return $this->firstLineIgnored;
    }
}
