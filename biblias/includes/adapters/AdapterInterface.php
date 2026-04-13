<?php
/**
 * Interface que todos os adapters devem implementar.
 * extract() retorna array de rows: [book_num, chapter, verse, text]
 */
interface AdapterInterface
{
    public function extract(): array;
}
