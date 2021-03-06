<?php

namespace BaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class CreateClientCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('api:oauth-server:client:create')
            ->setDescription('Creates a new client')
            ->addOption(
                'name',
                null,
                InputOption::VALUE_REQUIRED,
                'Set client name(user, android, ios, etc).',
                null
            )
            ->addOption(
                'redirect-uri',
                'uri',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
                ['http://127.0.0.1']
            )
            ->addOption(
                'grant-type',
                'type',
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types..',
                null
            )
            ->setHelp(
                <<<EOT
                    The <info>%command.name%</info>command creates a new client.

<info>php %command.full_name% [--redirect-uri=...] [--grant-type=...] name</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();

        $clientName = $input->getOption('name');
        $client->setName($clientName);
        $client->setRedirectUris($input->getOption('redirect-uri'));

        $grantTypes = $input->getOption('grant-type');

        if(is_string($grantTypes)){
            $grantTypes = @explode(" ", $grantTypes);
        }

        $client->setAllowedGrantTypes($grantTypes);
        $clientManager->updateClient($client);
        $output->writeln(
            sprintf(
                "Added a new client($clientName):\n Client id(client_id): <info>%s</info> \n Client secret(client_secret): <info>%s</info> \n Available grant types: <info>%s</info> \n",
                $client->getPublicId(),
                $client->getSecret(),
                implode(" ", $grantTypes)
            )
        );
    }

    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();


        if (!$input->getOption('name')) {
            $question = new Question('Please choose a new client name, ex.:(ios, android, angular):');
            $question->setValidator(function ($name) {
                if (empty($name)) {
                    throw new \Exception('Name can not be empty');
                }

                return $name;
            });
            $questions['name'] = $question;
        }

        if (!$input->getOption('redirect-uri')) {
            $question = new Question('Please choose an redirect-uri [ default: http://127.0.0.1 ]: ');

            $question->setValidator(function ($redirectUri) {
                if (empty($redirectUri)) {
                    $redirectUri = "http://127.0.0.1";
                }

                return $redirectUri;
            });
            $questions['redirect-uri'] = $question;
        }

        $avilableType = ['password', 'refresh_token', 'authorization_code', 'client_credentials'];
        if (!$input->getOption('grant-type')) {
            $questionText = sprintf('Please choose a grant-type, available options [%s]: ', implode(", ", $avilableType));
            $question = new Question($questionText);
            $question->setNormalizer(function ($grantType) use ($avilableType){

                $grantTypes = @explode(" ", $grantType);
                foreach ($grantTypes as $grantType) {
                    if (!@in_array($grantType, $avilableType)) {
                        throw new \Exception(
                            sprintf('Available Grant type: %s', "\n- " . implode("\n- ", $avilableType))
                        );

                    }
                }

                return $grantTypes;
            });

            $question->setAutocompleterValues($avilableType);

            $question->setValidator(function ($grantType) use ($avilableType){
                if (empty($grantType)) {
                    throw new \Exception('Grant type can not be empty');
                }



                return $grantType;
            });
            $questions['grant-type'] = $question;
        }


        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setOption($name, $answer);
        }
    }
}
