<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeDto extends Command
{
    /**
     * Assinatura do comando no terminal.
     * * Argumentos:
     * {name}         -> Nome da classe (ex: User ou Users/User)
     * * Opções (Flags):
     * {--r|response} -> Gera o template com fromModel e sufixo ResponseDTO
     * {--A|array}    -> Gera o template com fromArray (entrada de dados puros) : sendo o padrão fromRequest(entrada de dados validados)
     * {--N|no-suffix}-> Ignora a regra de adicionar "DTO" ou "ResponseDTO" automaticamente 
     */
    protected $signature = 'make:dto {name} {--r|response} {--A|array} {--N|no-suffix}';

    /**
     * Descrição do comando.
     */
    protected $description = 'Gera uma classe DTO. Use -r para ResponseDTO, -A para Arrays e -N para ignorar sufixos.';

    public function handle()
    {
        $name = $this->argument('name');
        $isResponse = $this->option('response');
        $isArray = $this->option('array');
        $noSuffix = $this->option('no-suffix');

        if ($isResponse && $isArray) {
            $this->error('A flag -A (--array) serve apenas para dados de entrada e não pode ser usada junto com -r (--response).');
            return Command::FAILURE;
        }

        $name = trim($name, '/\\');

        // Inteligência de Sufixo: DTO vs ResponseDTO
        if (!$noSuffix) {
            if ($isResponse) {
                // Remove "DTO" do final se o dev digitou por hábito (ex: UserDTO -> User)
                if (Str::endsWith(strtolower($name), 'dto')) {
                    $name = substr($name, 0, -3);
                }
                
                // Remove "Response" do final se o dev já digitou (ex: UserResponse -> User)
                // Isso evita gerar anomalias como UserResponseResponseDTO
                if (Str::endsWith(strtolower($name), 'response')) {
                    $name = substr($name, 0, -8);
                }
                
                // Aplica o sufixo completo e padronizado
                $name .= 'ResponseDTO';
            } else {
                // Adiciona ou padroniza "DTO" simples
                if (!Str::endsWith(strtolower($name), 'dto')) {
                    $name .= 'DTO';
                } else {
                    $name = substr($name, 0, -3) . 'DTO';
                }
            }
        }

        $directory = app_path('DTOs');
        $namespace = 'App\DTOs';

        $cleanName = str_replace('\\', '/', $name);
        
        $path = $directory . '/' . $cleanName . '.php';
        $className = class_basename($name);
        
        $subNamespace = str_replace('/', '\\', dirname($cleanName));
        if ($subNamespace !== '.') {
            $namespace .= '\\' . $subNamespace;
        }

        if (File::exists($path)) {
            $this->error("A classe [{$name}] já existe!");
            return Command::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        if ($isResponse) {
            $content = $this->getResponseStub($namespace, $className);
        } elseif ($isArray) {
            $content = $this->getArrayStub($namespace, $className);
        } else {
            $content = $this->getRequestStub($namespace, $className);
        }

        File::put($path, $content);

        $this->info("Classe [{$className}] gerada com sucesso em: {$path}");
        
        return Command::SUCCESS;
    }

    private function getRequestStub(string $namespace, string $className): string
    {
        return <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    public function __construct(
        // public readonly string \$exemplo,
    ) {}

    public static function fromRequest(array \$data): self
    {
        return new self(
            // exemplo: \$data['exemplo'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            // 'exemplo' => \$this->exemplo,
        ];
    }
}

PHP;
    }

    private function getResponseStub(string $namespace, string $className): string
    {
        return <<<PHP
<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Model;

class {$className}
{
    public function __construct(
        // public readonly string \$exemplo,
    ) {}

    public static function fromModel(Model \$model): self
    {
        return new self(
            // exemplo: \$model->exemplo,
        );
    }

    public function toArray(): array
    {
        return [
            // 'exemplo' => \$this->exemplo,
        ];
    }
}

PHP;
    }

    private function getArrayStub(string $namespace, string $className): string
    {
        return <<<PHP
<?php

namespace {$namespace};

class {$className}
{
    public function __construct(
        // public readonly string \$exemplo,
    ) {}

    public static function fromArray(array \$data): self
    {
        return new self(
            // exemplo: \$data['exemplo'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            // 'exemplo' => \$this->exemplo,
        ];
    }
}

PHP;
    }
}