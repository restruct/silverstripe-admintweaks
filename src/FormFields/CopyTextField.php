<?php

namespace Restruct\Silverstripe\AdminTweaks\FormFields;

use SilverStripe\Forms\TextField;

/**
 * A read-only text field with a copy-to-clipboard button.
 *
 * Features:
 * - Shows an input field with a copy button (Bootstrap 4 bi-copy icon)
 * - On copy: button turns green (btn-success) with checkmark icon for 2 seconds
 * - Optional button label (e.g., "Kopieer" / "Copy")
 * - Optional alert/prompt after copying (disabled by default)
 *
 * Usage:
 * ```php
 * CopyTextField::create('MyField', 'Label', 'Value to copy')
 *     ->setButtonLabel('Kopieer')
 *     ->setShowAlert(true)
 *     ->setAlertMessage('Copied to clipboard!');
 * ```
 */
class CopyTextField extends TextField
{
    /**
     * Whether to show an alert after copying.
     */
    protected bool $showAlert = false;

    /**
     * Custom alert message (if showAlert is true).
     */
    protected ?string $alertMessage = null;

    /**
     * CSS classes for the copy button.
     */
    protected string $buttonClasses = 'btn btn-outline-secondary';

    /**
     * Whether the input should be readonly.
     */
    protected bool $isReadonly = true;

    /**
     * Button label text (e.g., "Copy", "Kopieer").
     */
    protected ?string $buttonLabel = null;

    /**
     * Button title/tooltip text.
     */
    protected ?string $buttonTitle = null;

    /**
     * Set whether to show an alert after copying.
     */
    public function setShowAlert(bool $showAlert): static
    {
        $this->showAlert = $showAlert;
        return $this;
    }

    /**
     * Get whether to show an alert after copying.
     */
    public function getShowAlert(): bool
    {
        return $this->showAlert;
    }

    /**
     * Set custom alert message.
     */
    public function setAlertMessage(?string $message): static
    {
        $this->alertMessage = $message;
        return $this;
    }

    /**
     * Get the alert message.
     */
    public function getAlertMessage(): ?string
    {
        return $this->alertMessage;
    }

    /**
     * Set custom button CSS classes.
     */
    public function setButtonClasses(string $classes): static
    {
        $this->buttonClasses = $classes;
        return $this;
    }

    /**
     * Get button CSS classes.
     */
    public function getButtonClasses(): string
    {
        return $this->buttonClasses;
    }

    /**
     * Set whether the input is readonly.
     */
    public function setIsReadonly(bool $readonly): static
    {
        $this->isReadonly = $readonly;
        return $this;
    }

    /**
     * Get whether the input is readonly.
     */
    public function getIsReadonly(): bool
    {
        return $this->isReadonly;
    }

    /**
     * Set button label text.
     */
    public function setButtonLabel(?string $label): static
    {
        $this->buttonLabel = $label;
        return $this;
    }

    /**
     * Get button label text.
     */
    public function getButtonLabel(): ?string
    {
        return $this->buttonLabel;
    }

    /**
     * Set button title/tooltip text.
     */
    public function setButtonTitle(?string $title): static
    {
        $this->buttonTitle = $title;
        return $this;
    }

    /**
     * Get button title/tooltip text.
     */
    public function getButtonTitle(): ?string
    {
        return $this->buttonTitle;
    }

    /**
     * Get template data.
     */
    public function getTemplateData(): array
    {
        return [
            'ShowAlert' => $this->showAlert,
            'AlertMessage' => $this->alertMessage,
            'ButtonClasses' => $this->buttonClasses,
            'IsReadonly' => $this->isReadonly,
            'ButtonLabel' => $this->buttonLabel,
            'ButtonTitle' => $this->buttonTitle,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function Field($properties = [])
    {
        $properties = array_merge($properties, $this->getTemplateData());
        return parent::Field($properties);
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplates(): array
    {
        return [
            'Restruct/Silverstripe/AdminTweaks/FormFields/CopyTextField',
        ];
    }
}
