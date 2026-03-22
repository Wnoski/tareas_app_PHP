<?php
// Namespace del test — convención estándar, los tests viven en el namespace Tests
namespace Tests;

// Importamos la clase base de PHPUnit — todos los tests deben extenderla
// Nos da acceso a todos los métodos assert y al ciclo de vida de los tests
use PHPUnit\Framework\TestCase;

// La clase de test debe extender TestCase
// El nombre debe terminar en Test — PHPUnit la detecta automáticamente
class ValidationTest extends TestCase
{
    // Cada método de test debe empezar con 'test' — PHPUnit lo ejecuta automáticamente
    // :void indica que no devuelve nada, solo verifica condiciones
    public function testEmailValido(): void
    {
        // assertTrue — verifica que la función devuelve true con un email válido
        // Si devuelve false el test falla y PHPUnit lo reporta como error
        $this->assertTrue(\App\Helpers\validarEmail('usuario@email.com'));
    }

    public function testEmailInvalido(): void
    {
        // assertFalse — verifica que la función devuelve false con un email malformado
        // 'esto-no-es-un-email' no tiene @ ni dominio — debe fallar la validación
        $this->assertFalse(\App\Helpers\validarEmail('esto-no-es-un-email'));
    }

    public function testEmailVacio(): void
    {
        // Caso límite — string vacío también debe devolver false
        // Es importante probar los casos límite, no solo el caso feliz
        $this->assertFalse(\App\Helpers\validarEmail(''));
    }
}