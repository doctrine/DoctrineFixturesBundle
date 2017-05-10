<?php
namespace Symfony\Bundle\DoctrineFixturesBundle\Command;

use \Symfony\Component\Console\Input\InputArgument;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;

use \Symfony\Bundle\DoctrineBundle\Command\DoctrineCommand;

/**
 * class GenerateFixturesDoctrineCommand extends Command
 * 
 * This class prints the PHP code corresponding to an entity from the DB.
 * 
 * @version     1.0
 * 
 * @author      Antoine Durieux
 */
class GenerateFixturesDoctrineCommand extends DoctrineCommand
{
    // =========================================================================
    // Properties
    // =========================================================================
    
    /**
     * The output channel.
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;
    
    /**
     * Describes the way the associations of the object will be generated.
     * @var string
     */
    private $mode;
    
    /**
     * Map of the generated entities to avoid recursion.
     * @var type 
     */
    private $generatedMap;
    
    /**
     * The Entity Manager to use.
     * @var \Doctrine\ORM\EntityManager
     */
    private $em;
    
    // =========================================================================
    // Constants
    // =========================================================================
    
    /**
     * Associations of the base object will be generated as Doctrine references.
     * @var string 
     */
    const MODE_REFERENCES = 'references';
    /**
     * Associations of the base object will be recursively generated as PHP code.
     * @var string 
     */
    const MODE_PHP_CODE = 'php-code';
    /**
     * Associations of the base object will be generated as existing PHP variables.
     * @var string 
     */
    const MODE_PHP_VARIABLES = 'php-variables';
    
    // =========================================================================
    // Configuration
    // =========================================================================
    
    /**
     * protected function configure()
     * 
     * Configuration function.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     */
    protected function configure()
    {
        $this
            ->setName('doctrine:fixtures:generate')
            ->setDescription('Outputs PHP code for fixture generation.')
            ->addArgument('class', InputArgument::REQUIRED, 'Fully qualified class name.')
            ->addArgument('id', InputArgument::REQUIRED, 'Id of the entity to mock.')
            ->addArgument('mode', InputArgument::OPTIONAL, 'Generation mode of the associated entities.',self::MODE_REFERENCES)
            ->setHelp(
<<<EOT
This command prints in the terminal the PHP code that would be equivalent to 
the generation of the same entity from PHP.

It's very usefull when building quick fixtures from an existing database.

<comment>Example use:</comment>
<info>php app/console doctrine:fixtures:generate</info> 'Acme\DemoBundle\Entity\Product' 1234 references
<info>php app/console doctrine:fixtures:generate</info> 'Acme\DemoBundle\Entity\Category' 14 php-code
EOT
            )
        ;
    }

    // =========================================================================
    // Logic
    // =========================================================================
    
    /**
     * protected function execute(InputInterface $input, OutputInterface $output)
     * 
     * Main function.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mode = $input->getArgument('mode');
        $this->output = $output;
        $this->em = $this->getContainer()->get('doctrine.orm.entity_manager');
        
        $id = $input->getArgument('id');
        $className = $input->getArgument('class');
        
        $this->generateEntity($className, $id);
    }
    
    
    /**
     * private function generateEntity($className,$id)
     * 
     * This function generates the PHP code equivalent to one entity.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     * 
     * @param       string      $className
     * @param       int         $id
     * @throws      \Exception 
     * @return      GenerateFixturesDoctrineCommand
     */
    private function generateEntity($className,$id)
    {
        $reducedClassName = self::getEntityShortName($className);
        $entityName = '$'.$reducedClassName.$id;
        
        // ---------------------------------------------------------------------
        // 0. Verify it the current entity has been generated yet
        // ---------------------------------------------------------------------
        if(!isset($this->generatedMap[$className])){
            $this->generatedMap[$className] = array();
        }
        if(isset($this->generatedMap[$className][$id])){
            return $entityName;
            $this->output->writeln('// Entity '.$className.' - '.$id.' was already generated.');
        }
        $this->generatedMap[$className][$id] = true;
        
        $this->output->writeln('// ---------------------------------------------------------');
        $this->output->writeln('// Generating Fixture for entity '.$className.' - '.$id);
        $this->output->writeln('// ---------------------------------------------------------');
        
        // ---------------------------------------------------------------------
        // 1. Load data & metadata
        // ---------------------------------------------------------------------
        
        $metadata = $this->em->getClassMetadata($className);
        $entity = $this->em->getRepository($className)->find($id);
        
        // ---------------------------------------------------------------------
        // 1. Create entity
        // ---------------------------------------------------------------------

        $this->output->writeln('');
        $this->output->writeln('// Creating object :');
        $this->output->writeln($entityName.' = new '.$className.'();');
        
        // ---------------------------------------------------------------------
        // 2. Field mappings
        // ---------------------------------------------------------------------
        
        $this->output->writeln('');
        $this->output->writeln('// Field mappings :');
        foreach($metadata->fieldMappings as $fieldName => $fieldMetadata)
        {
            $setter = self::getSetter($fieldName);
            $getter = self::getGetter($fieldName);
            
            // Construct the value string
            switch($fieldMetadata['type'])
            {
                
                case 'boolean':
                    $value = ($entity->$getter() ? 'true' : 'false');
                    break;
                
                case 'datetime':
                case 'date':
                    $value = "\DateTime::createFromFormat(\DateTime::ISO8601, '".$entity->$getter()->format(\DateTIme::ISO8601)."')";
                    break;
                
                case 'decimal':
                    $value = $entity->$getter();
                    break;
                
                case 'integer':
                case 'smallint':
                    $value = $entity->$getter();
                    break;
                
                case 'string':
                case 'text':
                    $value = "'".$entity->$getter()."'";
                    break;
                    
                default:
                    throw new \Exception('Type '.$fieldMetadata['type'].' not supported yet');
                    break;
            }
            
            if($value != ''){
                $this->output->writeln($entityName.'->'.$setter.'('.$value.');');
            }else{
                $this->output->writeln('// '.$entityName.'->'.$setter.'(null);');
            }
        }
        
        // ---------------------------------------------------------------------
        // 3. Association mappings :
        // ---------------------------------------------------------------------
        
        $this->output->writeln('');
        $this->output->writeln('// Association mappings :');
        foreach($metadata->associationMappings as $associationName => $associationMetadata)
        {
            // Only keep association side.
            if($associationMetadata['isOwningSide'] == 1)
            {
                $setter = self::getSetter($associationName);
                $getter = self::getGetter($associationName);
                
                // Verify if not null
                $association = $entity->$getter();
                if($association === null){
                    $this->output->writeln('// '.$entityName.'->'.$setter.'(null);');
                    continue;
                }
                
                // Load information
                $targetId = $association->getId();
                $targetClass = $associationMetadata['targetEntity'];
                $targetClassName = self::getEntityShortName($targetClass);
                
                // Output depending on mode
                switch($this->mode){
                    case self::MODE_REFERENCES:
                        $value = '$em->getReference(\''.$targetClass.'\','.$targetId.')';
                        $this->output->writeln($entityName.'->'.$setter.'('.$value.');');
                        break;
                    
                    case self::MODE_PHP_VARIABLES:
                        $value = '$'.$targetClassName.$targetId;
                        $this->output->writeln($entityName.'->'.$setter.'('.$value.');');
                        break;
                        
                    case self::MODE_PHP_CODE:
                        $value = $this->generateEntity($targetClass, $targetId);
                        $this->output->writeln($entityName.'->'.$setter.'('.$value.');');
                        break;
                        
                    default:
                        throw new \Exception('Mode '.$this->mode.' not supported');
                        break;
                        
                        
                }
            }
        }
        
        $this->output->writeln('');
        
        return $entityName;
    }

    // =========================================================================
    // Helpers
    // =========================================================================
    
    /**
     * private static function getSetter($fieldName)
     * 
     * Returns a quick and dirty estimation of the setter name.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     *
     * @param       string      $fieldName
     * @return      string      
     */
    private static function getSetter($fieldName){
        $fieldName = str_replace('_','',$fieldName);
        return 'set'.ucFirst($fieldName);
    }
    
    /**
     * private static function getGetter($fieldName)
     * 
     * Returns a quick and dirty estimation of the getter name.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     *
     * @param       string      $fieldName
     * @return      string      
     */
    private static function getGetter($fieldName){
        $fieldName = str_replace('_','',$fieldName);
        return 'get'.ucFirst($fieldName);
    }
    
    /**
     * private static function getEntityShortName($className)
     * 
     * Returns the short name of a given class.
     * 
     * @version     1.0
     * 
     * @author      Antoine Durieux
     *
     * @param       string      $className
     * @return      string
     */
    private static function getEntityShortName($className){
        $result = array();
        preg_match("#([a-zA-Z]*)$#", $className,$result);
        return $result[1];
    }
}
