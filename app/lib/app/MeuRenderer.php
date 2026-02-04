<?php
class MenuRenderer
{
    private array $items = [];
    private string $nomeMenu;

    public function __construct(string $xmlFile, string $Menu = 'MENU')
    {
        $xml = simplexml_load_file($xmlFile);

        foreach ($xml->menuitem as $item) {
            $this->items[] = [
                'action' => (string) $item->action,
                'label'  => (string) $item->label,
                'mobile' => ((string) $item->mobile === 'Y'),
            ];
        }
        $this->nomeMenu = $Menu;
    }

    public function render(string $htmlTemplate): string
    {
        $menuHtml = '';

        foreach ($this->items as $item) {
            $menuHtml .= "<ul style='font-family: Arial, sans-serif; font-size: 0.8em'>";
            $menuHtml .= "<strong><a href=\"index.php?class={$item['action']}\">";
            $menuHtml .= htmlspecialchars($item['label']);
            $menuHtml .= "</a></strong></ul>\n";
        }

        return str_replace('{' . $this->nomeMenu . '}', $menuHtml, $htmlTemplate);
    }
}
