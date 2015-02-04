<?php

namespace Khepin\YamlFixturesBundle\Fixture;

use Coala\MappingBundle\Data\AutoMapper;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Util\Inflector;
use JMS\Serializer\DeserializationContext;
use JMS\Serializer\Naming\IdenticalPropertyNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Form\DataMapperInterface;

class CommandHandlerFixture extends AbstractFixture
{
    public function load(ObjectManager $manager, $tags = null)
    {
        $this->manager = $manager;
        $class = $this->file['model'];

        foreach ($this->file['fixtures'] as $reference => $fixture_data) {
            $this->createObject($class, $fixture_data, null);
        }
    }

    public function createObject($class, $data, $metadata, $options = array())
    {
        $mapper = new AutoMapper($class, null, true);

        $class = new \ReflectionClass($class);
        $constructArguments = array();
        if (isset($data['__construct'])) {
            $arguments = $data['__construct'];
            if (is_array($arguments)) {
                foreach($arguments as $argument) {
                    if (is_array($argument)) {
                        if ($argument['type'] == 'datetime') {
                            $constructArguments[] = new \DateTime($argument['value']);
                        } elseif ($argument['type'] == 'reference') {
                            $constructArguments[] = $this->loader->getReference($argument['value']);
                        } else {
                            $constructArguments[] = $argument['value'];
                        }
                    } else {
                        $constructArguments[] = $argument;
                    }
                }
            } else {
                $constructArguments[] = $arguments;
            }
            unset($data['__construct']);
        }

        $dto = $class->newInstanceArgs($constructArguments);

        $mapper->toggleResultTyp(AutoMapper::RESULT_OBJECT);
        $object = $mapper->mapDataFromJs($data, $dto);

        $this->runServiceCalls($object);

        return $object;
    }
}
