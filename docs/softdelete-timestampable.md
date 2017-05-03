Use Gedmo library to implement SoftDelete and Timestampable:

```bash
 
  composer require gedmo/doctrine-extensions
```  

Just configure your `service.yml`

```yaml

    gedmo.listener.timestampable:
        class: Gedmo\Timestampable\TimestampableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]
``` 

```yaml
    gedmo.listener.softdeleteablelistener:
        class: Gedmo\SoftDeleteable\SoftDeleteableListener
        tags:
            - { name: doctrine.event_subscriber, connection: default }
        calls:
            - [ setAnnotationReader, [ "@annotation_reader" ] ]
``` 

Just modify your entity:

```php
    
    use use Gedmo\Mapping\Annotation as Gedmo;
    
    /**
     * @var \DateTime
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     */
    protected $createdAt;
    

```


```php
    
    use use Gedmo\Mapping\Annotation as Gedmo;
     

  /**
   * @ORM\Entity(repositoryClass="AppBundle\Entity\Repository\TestRepository")
   * @ORM\Table(name="test")
   * @Gedmo\SoftDeleteable(fieldName="deletedAt", timeAware=false)
   */
  class Test 
  {
       /**
       * @ORM\Column(name="deletedAt", type="datetime", nullable=true)
       */
      protected $deletedAt;
  }
    

```


