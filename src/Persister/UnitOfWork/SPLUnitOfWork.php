<?php

namespace Kiboko\Component\BatchORM\Persister\UnitOfWork;

use Symfony\Component\Serializer\SerializerInterface;

class SPLUnitOfWork implements UnitOfWorkInterface
{
    /**
     * @var \SplFileObject
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
     * @param \SplFileInfo $fileInfo
     * @param SerializerInterface $serializer
     * @param bool $firstLineIgnored
     */
    public function __construct(
        \SplFileInfo $fileInfo,
        SerializerInterface $serializer,
        $firstLineIgnored = false
    ) {
        $this->file = $fileInfo->openFile('w');
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
        $this->file->fwrite($this->serializer->serialize($value, 'csv'));
    }

    /**
     * @return bool
     */
    public function isFirstLineIgnored()
    {
        return $this->firstLineIgnored;
    }
}
