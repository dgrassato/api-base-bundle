<?php

namespace BaseBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * @author Diego Pereira Grassato <diego.grassato@gmail.com>
 */
class CreateClientCommand1 extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('api:oauth-server2:client:create')
            ->setDescription('Creates a new oauth client')
            ->addArgument(
                'name',
                InputArgument::REQUIRED,
                'Set client name(android, ios, etc).',
                null
            )

            ->addArgument(
                'redirect-uri',
                InputArgument::REQUIRED,
                'Sets redirect uri for client. Use this option multiple times to set multiple redirect URIs.',
                null
            )
            ->addArgument(
                'grant-type',
                InputArgument::REQUIRED | InputArgument::IS_ARRAY,
                'Sets allowed grant type for client. Use this option multiple times to set multiple grant types..',
                null
            )
            ->setHelp(
                <<<'EOT'
                    The <info>%command.name%</info> command creates a new client.

<info>php %command.full_name% [--name=] [--redirect-uri=] [--grant-type=...]</info>

EOT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $clientManager = $this->getContainer()->get('fos_oauth_server.client_manager.default');
        $client = $clientManager->createClient();
        $client->setName($input->getArgument('name'));
        $client->setRedirectUris($input->getArguments('redirect-uri'));
        $client->setAllowedGrantTypes($input->getArguments('grant-type'));
        $clientManager->updateClient($client);

        $output->writeln(
            sprintf(
                "Added a new client:\n Client id: <info>%s</info> \n Client secret: <info>%s</info>\n",
                $client->getPublicId(),
                $client->getSecret()
            )
        );
        $output->writeln(
            sprintf(
                '/oauth/v2/token?client_id=%s&client_secret=%s&grant_type=%s',
                $client->getPublicId(),
                $client->getSecret(),
                'client_credentials'
            )
        );
//        $output->writeln(
//            sprintf(
//                '/oauth/v2/auth?client_id=%s&redirect_uri=%s&response_type=token',
//                $client->getPublicId(),
//                'http://symfony.dev'
//            )
//        );
    }

    /**
     * {@inheritdoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questions = array();


        if (!$input->getArgument('name')) {
            $question = new Question('Please choose a new client name, ex.:(ios, android, angular):');
            $question->setValidator(function ($name) {
                if (empty($name)) {
                    throw new \Exception('Name can not be empty');
                }

                return $name;
            });
            $questions['name'] = $question;
        }

        if (!$input->getArgument('redirect-uri')) {
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
        if (!$input->getArgument('grant-type')) {
            $questionText = sprintf('Please choose a grant-type, available options [%s]: ', implode(", ", $avilableType));
            $question = new Question($questionText);
            $question->setValidator(function ($grantType) use ($avilableType){
                if (empty($grantType)) {
                    throw new \Exception('Grant type can not be empty');
                }

                if (!@in_array($avilableType, $grantType)){
                    throw new \Exception(
                        sprintf('Available Grant type: %s', "\n- ".implode("\n- ", $avilableType))
                     );

                }

                return $grantType;
            });
            $questions['grant-type'] = $question;
        }


        foreach ($questions as $name => $question) {
            $answer = $this->getHelper('question')->ask($input, $output, $question);
            $input->setArgument($name, $answer);
        }
    }
}
