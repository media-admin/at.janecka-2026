<?php

declare(strict_types=1);

namespace Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\Driver;

use Doctrine\Common\Annotations\Reader;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Accessor;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\AccessorOrder;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\AccessType;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Discriminator;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Exclude;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\ExclusionPolicy;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Expose;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Groups;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Inline;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\MaxDepth;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\PostDeserialize;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\PostSerialize;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\PreSerialize;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\ReadOnlyProperty;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\SerializedName;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\SerializerAttribute;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Since;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\SkipWhenEmpty;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Type;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\UnionDiscriminator;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\Until;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\VirtualProperty;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlAttribute;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlAttributeMap;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlDiscriminator;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlElement;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlKeyValuePairs;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlList;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlMap;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlNamespace;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlRoot;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Annotation\XmlValue;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Exception\InvalidMetadataException;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Expression\CompilableExpressionEvaluatorInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ClassMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\ExpressionPropertyMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\PropertyMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Metadata\VirtualPropertyMetadata;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Naming\PropertyNamingStrategyInterface;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Type\Parser;
use Vendidero\StoreaBill\Vendor\JMS\Serializer\Type\ParserInterface;
use Vendidero\StoreaBill\Vendor\Metadata\ClassMetadata as BaseClassMetadata;
use Vendidero\StoreaBill\Vendor\Metadata\Driver\DriverInterface;
use Vendidero\StoreaBill\Vendor\Metadata\MethodMetadata;

class AnnotationOrAttributeDriver implements DriverInterface
{
    use ExpressionMetadataTrait;

    /**
     * @var ParserInterface
     */
    private $typeParser;

    /**
     * @var PropertyNamingStrategyInterface
     */
    private $namingStrategy;

    /**
     * @var Reader
     */
    private $reader;

    public function __construct(PropertyNamingStrategyInterface $namingStrategy, ?ParserInterface $typeParser = null, ?CompilableExpressionEvaluatorInterface $expressionEvaluator = null, ?Reader $reader = null)
    {
        $this->typeParser = $typeParser ?: new Parser();
        $this->namingStrategy = $namingStrategy;
        $this->expressionEvaluator = $expressionEvaluator;
        $this->reader = $reader;
    }

    public function loadMetadataForClass(\ReflectionClass $class): ?BaseClassMetadata
    {
        $configured = false;

        $classMetadata = new ClassMetadata($name = $class->name);
        $fileResource =  $class->getFilename();

        if (false !== $fileResource) {
            $classMetadata->fileResources[] = $fileResource;
        }

        $propertiesMetadata = [];
        $propertiesAnnotations = [];

        $exclusionPolicy = ExclusionPolicy::NONE;
        $excludeAll = false;
        $classAccessType = PropertyMetadata::ACCESS_TYPE_PROPERTY;
        $readOnlyClass = false;

        foreach ($this->getClassAnnotations($class) as $annot) {
            $configured = true;

            if ($annot instanceof ExclusionPolicy) {
                $exclusionPolicy = $annot->policy;
            } elseif ($annot instanceof XmlRoot) {
                $classMetadata->xmlRootName = $annot->name;
                $classMetadata->xmlRootNamespace = $annot->namespace;
                $classMetadata->xmlRootPrefix = $annot->prefix;
            } elseif ($annot instanceof XmlNamespace) {
                $classMetadata->registerNamespace($annot->uri, $annot->prefix);
            } elseif ($annot instanceof Exclude) {
                if (null !== $annot->if) {
                    $classMetadata->excludeIf = $this->parseExpression($annot->if);
                } else {
                    $excludeAll = true;
                }
            } elseif ($annot instanceof AccessType) {
                $classAccessType = $annot->type;
            } elseif ($annot instanceof ReadOnlyProperty) {
                $readOnlyClass = true;
            } elseif ($annot instanceof AccessorOrder) {
                $classMetadata->setAccessorOrder($annot->order, $annot->custom);
            } elseif ($annot instanceof Discriminator) {
                if ($annot->disabled) {
                    $classMetadata->discriminatorDisabled = true;
                } else {
                    $classMetadata->setDiscriminator($annot->field, $annot->map, $annot->groups);
                }
            } elseif ($annot instanceof XmlDiscriminator) {
                $classMetadata->xmlDiscriminatorAttribute = (bool) $annot->attribute;
                $classMetadata->xmlDiscriminatorCData = (bool) $annot->cdata;
                $classMetadata->xmlDiscriminatorNamespace = $annot->namespace ? (string) $annot->namespace : null;
            } elseif ($annot instanceof VirtualProperty) {
                $virtualPropertyMetadata = new ExpressionPropertyMetadata(
                    $name,
                    $annot->name,
                    $this->parseExpression($annot->exp),
                );
                $propertiesMetadata[] = $virtualPropertyMetadata;
                $propertiesAnnotations[] = $annot->options;
            }
        }

        foreach ($class->getMethods() as $method) {
            if ($method->class !== $name) {
                continue;
            }

            $methodAnnotations = $this->getMethodAnnotations($method);

            foreach ($methodAnnotations as $annot) {
                $configured = true;

                if ($annot instanceof PreSerialize) {
                    $classMetadata->addPreSerializeMethod(new MethodMetadata($name, $method->name));
                    continue 2;
                } elseif ($annot instanceof PostDeserialize) {
                    $classMetadata->addPostDeserializeMethod(new MethodMetadata($name, $method->name));
                    continue 2;
                } elseif ($annot instanceof PostSerialize) {
                    $classMetadata->addPostSerializeMethod(new MethodMetadata($name, $method->name));
                    continue 2;
                } elseif ($annot instanceof VirtualProperty) {
                    $virtualPropertyMetadata = new VirtualPropertyMetadata($name, $method->name);
                    $propertiesMetadata[] = $virtualPropertyMetadata;
                    $propertiesAnnotations[] = $methodAnnotations;
                    continue 2;
                }
            }
        }

        if (!$excludeAll) {
            foreach ($class->getProperties() as $property) {
                if ($property->class !== $name || (isset($property->info) && $property->info['class'] !== $name)) {
                    continue;
                }

                $propertiesMetadata[] = new PropertyMetadata($name, $property->getName());
                $propertiesAnnotations[] = $this->getPropertyAnnotations($property);
            }

            foreach ($propertiesMetadata as $propertyKey => $propertyMetadata) {
                $isExclude = false;
                $isExpose = $propertyMetadata instanceof VirtualPropertyMetadata
                    || $propertyMetadata instanceof ExpressionPropertyMetadata;
                $propertyMetadata->readOnly = $propertyMetadata->readOnly || $readOnlyClass;
                $accessType = $classAccessType;
                $accessor = [null, null];

                $propertyAnnotations = $propertiesAnnotations[$propertyKey];

                foreach ($propertyAnnotations as $annot) {
                    $configured = true;

                    if ($annot instanceof Since) {
                        $propertyMetadata->sinceVersion = $annot->version;
                    } elseif ($annot instanceof Until) {
                        $propertyMetadata->untilVersion = $annot->version;
                    } elseif ($annot instanceof SerializedName) {
                        $propertyMetadata->serializedName = $annot->name;
                    } elseif ($annot instanceof SkipWhenEmpty) {
                        $propertyMetadata->skipWhenEmpty = true;
                    } elseif ($annot instanceof Expose) {
                        $isExpose = true;
                        if (null !== $annot->if) {
                            $propertyMetadata->excludeIf = $this->parseExpression('!(' . $annot->if . ')');
                        }
                    } elseif ($annot instanceof Exclude) {
                        if (null !== $annot->if) {
                            $propertyMetadata->excludeIf = $this->parseExpression($annot->if);
                        } else {
                            $isExclude = true;
                        }
                    } elseif ($annot instanceof Type) {
                        $propertyMetadata->setType($this->typeParser->parse($annot->name));
                    } elseif ($annot instanceof XmlElement) {
                        $propertyMetadata->xmlAttribute = false;
                        $propertyMetadata->xmlElementCData = $annot->cdata;
                        $propertyMetadata->xmlNamespace = $annot->namespace;
                    } elseif ($annot instanceof XmlList) {
                        $propertyMetadata->xmlCollection = true;
                        $propertyMetadata->xmlCollectionInline = $annot->inline;
                        $propertyMetadata->xmlEntryName = $annot->entry;
                        $propertyMetadata->xmlEntryNamespace = $annot->namespace;
                        $propertyMetadata->xmlCollectionSkipWhenEmpty = $annot->skipWhenEmpty;
                    } elseif ($annot instanceof XmlMap) {
                        $propertyMetadata->xmlCollection = true;
                        $propertyMetadata->xmlCollectionInline = $annot->inline;
                        $propertyMetadata->xmlEntryName = $annot->entry;
                        $propertyMetadata->xmlEntryNamespace = $annot->namespace;
                        $propertyMetadata->xmlKeyAttribute = $annot->keyAttribute;
                    } elseif ($annot instanceof XmlKeyValuePairs) {
                        $propertyMetadata->xmlKeyValuePairs = true;
                    } elseif ($annot instanceof XmlAttribute) {
                        $propertyMetadata->xmlAttribute = true;
                        $propertyMetadata->xmlNamespace = $annot->namespace;
                    } elseif ($annot instanceof XmlValue) {
                        $propertyMetadata->xmlValue = true;
                        $propertyMetadata->xmlElementCData = $annot->cdata;
                    } elseif ($annot instanceof AccessType) {
                        $accessType = $annot->type;
                    } elseif ($annot instanceof ReadOnlyProperty) {
                        $propertyMetadata->readOnly = $annot->readOnly;
                    } elseif ($annot instanceof Accessor) {
                        $accessor = [$annot->getter, $annot->setter];
                    } elseif ($annot instanceof Groups) {
                        $propertyMetadata->groups = $annot->groups;
                        foreach ((array) $propertyMetadata->groups as $groupName) {
                            if (false !== strpos($groupName, ',')) {
                                throw new InvalidMetadataException(sprintf(
                                    'Invalid group name "%s" on "%s", did you mean to create multiple groups?',
                                    implode(', ', $propertyMetadata->groups),
                                    $propertyMetadata->class . '->' . $propertyMetadata->name,
                                ));
                            }
                        }
                    } elseif ($annot instanceof Inline) {
                        $propertyMetadata->inline = true;
                    } elseif ($annot instanceof XmlAttributeMap) {
                        $propertyMetadata->xmlAttributeMap = true;
                    } elseif ($annot instanceof MaxDepth) {
                        $propertyMetadata->maxDepth = $annot->depth;
                    } elseif ($annot instanceof UnionDiscriminator) {
                        $propertyMetadata->setType([
                            'name' => 'union',
                            'params' => [null, $annot->field, $annot->map],
                        ]);
                    }
                }

                if ($propertyMetadata->inline) {
                    $classMetadata->isList = $classMetadata->isList || PropertyMetadata::isCollectionList($propertyMetadata->type);
                    $classMetadata->isMap = $classMetadata->isMap || PropertyMetadata::isCollectionMap($propertyMetadata->type);

                    if ($classMetadata->isMap && $classMetadata->isList) {
                        throw new InvalidMetadataException('Cannot have an inline map and inline map on the same class');
                    }
                }

                if (!$propertyMetadata->serializedName) {
                    $propertyMetadata->serializedName = $this->namingStrategy->translateName($propertyMetadata);
                }

                foreach ($propertyAnnotations as $annot) {
                    if ($annot instanceof VirtualProperty && null !== $annot->name) {
                        $propertyMetadata->name = $annot->name;
                    }
                }

                if (
                    (ExclusionPolicy::NONE === $exclusionPolicy && !$isExclude)
                    || (ExclusionPolicy::ALL === $exclusionPolicy && $isExpose)
                ) {
                    $propertyMetadata->setAccessor($accessType, $accessor[0], $accessor[1]);
                    $classMetadata->addPropertyMetadata($propertyMetadata);
                }
            }
        }

        // if (!$configured) {
            // return null;
            // uncomment the above line after a couple of months
        // }

        return $classMetadata;
    }

    /**
     * @return list<SerializerAttribute>
     */
    protected function getClassAnnotations(\ReflectionClass $class): array
    {
        $annotations = [];

        if (PHP_VERSION_ID >= 80000) {
            $annotations = array_map(
                static fn (\ReflectionAttribute $attribute): object => $attribute->newInstance(),
                $class->getAttributes(SerializerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF),
            );
        }

        if (null !== $this->reader) {
            $annotations = array_merge($annotations, $this->reader->getClassAnnotations($class));
        }

        return $annotations;
    }

    /**
     * @return list<SerializerAttribute>
     */
    protected function getMethodAnnotations(\ReflectionMethod $method): array
    {
        $annotations = [];

        if (PHP_VERSION_ID >= 80000) {
            $annotations = array_map(
                static fn (\ReflectionAttribute $attribute): object => $attribute->newInstance(),
                $method->getAttributes(SerializerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF),
            );
        }

        if (null !== $this->reader) {
            $annotations = array_merge($annotations, $this->reader->getMethodAnnotations($method));
        }

        return $annotations;
    }

    /**
     * @return list<SerializerAttribute>
     */
    protected function getPropertyAnnotations(\ReflectionProperty $property): array
    {
        $annotations = [];

        if (PHP_VERSION_ID >= 80000) {
            $annotations = array_map(
                static fn (\ReflectionAttribute $attribute): object => $attribute->newInstance(),
                $property->getAttributes(SerializerAttribute::class, \ReflectionAttribute::IS_INSTANCEOF),
            );
        }

        if (null !== $this->reader) {
            $annotations = array_merge($annotations, $this->reader->getPropertyAnnotations($property));
        }

        return $annotations;
    }
}
