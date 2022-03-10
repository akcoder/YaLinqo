<?php

$finder = PhpCsFixer\Finder::create()
                           ->in(__DIR__ . '/src')
                           ->in(__DIR__ . '/tests');

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PHPUnit60Migration:risky'                     => true,
        '@Symfony'                                      => true,
        '@Symfony:risky'                                => true,
        'align_multiline_comment'                       => true,
        'phpdoc_to_comment'                             => false,
        //                               'array_indentation' => true,
        'array_syntax'                                  => ['syntax' => 'short'],
        'braces'                                        => [
            'allow_single_line_closure'                   => true,
            'position_after_functions_and_oop_constructs' => 'same',
            'position_after_control_structures'           => 'same',
            'position_after_anonymous_constructs'         => 'same',
        ],
        'blank_line_after_namespace'                    => true,
        'blank_line_before_statement'                   => true,
        'combine_consecutive_issets'                    => true,
        'combine_consecutive_unsets'                    => true,
        'comment_to_phpdoc'                             => [
            'ignored_tags' => [
                'codeCoverageIgnoreStart',
                'codeCoverageIgnoreEnd',
                'psalm-suppress',
            ],
        ],
        'compact_nullable_typehint'                     => true,
        //                               'escape_implicit_backslashes' => true,
        'explicit_indirect_variable'                    => true,
        'explicit_string_variable'                      => true,
        'final_internal_class'                          => true,
        'fully_qualified_strict_types'                  => true,
        'function_declaration'                          => ['closure_function_spacing' => 'none'],
        'function_to_constant'                          => [
            'functions' => [
                'get_class',
                'get_called_class',
                'php_sapi_name',
                'phpversion',
                'pi'
            ],
        ],
        'heredoc_to_nowdoc'                             => true,
        //                               'list_syntax'                                   => ['syntax' => 'long'],
        'logical_operators'                             => true,
        'method_argument_space'                         => [
            'on_multiline' => 'ignore',
        ],
        'method_chaining_indentation'                   => false,
        'multiline_comment_opening_closing'             => true,
        'no_alternative_syntax'                         => true,
        'no_binary_string'                              => true,
        'no_extra_blank_lines'                          => [
            'tokens' => [
                'break',
                'continue',
                'extra',
                'return',
                'throw',
                'use',
                'parenthesis_brace_block',
                'square_brace_block',
            ],
        ],
        'echo_tag_syntax'                               => ['format' => 'long'],
        'no_superfluous_elseif'                         => true,
        'no_unneeded_curly_braces'                      => true,
        'no_unneeded_final_method'                      => true,
        'no_unreachable_default_argument_value'         => true,
        'no_unset_on_property'                          => true,
        'no_useless_else'                               => true,
        'no_useless_return'                             => true,
        'ordered_class_elements'                        => true,
        'ordered_imports'                               => true,
        'php_unit_internal_class'                       => true,
        'phpdoc_order_by_value'                         => ['annotations' => ['covers']],
        'php_unit_set_up_tear_down_visibility'          => true,
        'php_unit_strict'                               => true,
        'php_unit_test_annotation'                      => true,
        'php_unit_test_case_static_method_calls'        => ['call_type' => 'self'],
        'php_unit_test_class_requires_covers'           => true,
        'phpdoc_order'                                  => true,
        'phpdoc_trim_consecutive_blank_line_separation' => true,
        'phpdoc_trim'                                   => true,
        'phpdoc_types_order'                            => [
            'null_adjustment' => 'always_last',
        ],
        'return_assignment'                             => true,
        'semicolon_after_instruction'                   => true,
        //                               'strict_param' => true,
        'string_line_ending'                            => true,
        //APT
        'single_line_comment_style'                     => false,
        'concat_space'                                  => ['spacing' => 'one'],
        'yoda_style'                                    => false,
        'no_leading_import_slash'                       => true,
        'native_constant_invocation'                    => false,
        'strict_comparison'                             => false,
        'no_null_property_initialization'               => false,
        'single_quote'                                  => false,
        'trailing_comma_in_multiline'                   => false,
        'strict_param'                                  => false,
        'array_indentation'                             => false,
        'class_attributes_separation'                   => false,
        'class_definition'                              => false,
        'no_blank_lines_after_class_opening'            => false,
        'native_function_invocation'                    => false,
        'binary_operator_spaces'                        => false,
        'trim_array_spaces'                             => false,
        'mb_str_functions'                              => true,
        'phpdoc_add_missing_param_annotation'           => [
            'only_untyped' => true,
        ],
        'no_mixed_echo_print'                           => [
            'use' => 'print',
        ],
        'lowercase_static_reference'                    => true,
        'lowercase_keywords'                            => true,
        'constant_case'                                 => ['case' => 'lower'],
        'line_ending'                                   => true,
        'is_null'                                       => true,
        'implode_call'                                  => true,
        'list_syntax'                                   => ['syntax' => 'short'],
        'fopen_flag_order'                              => true,
        'phpdoc_no_empty_return'                        => true,
        'single_line_throw'                             => false,
        'single_space_after_construct'                  => [
            'constructs' => [
                'abstract',
                'as',
                'attribute',
                'break',
                'case',
                'catch',
                'class',
                'clone',
                'comment',
                'const',
                'const_import',
                'continue',
                'do',
                'echo',
                'else',
                'elseif',
                'extends',
                'final',
                'finally',
                'for',
                'foreach',
                //'function',
                'function_import',
                'global',
                'goto',
                'if',
                'implements',
                'include',
                'include_once',
                'instanceof',
                'insteadof',
                'interface',
                'match',
                'named_argument',
                'new',
                'open_tag_with_echo',
                'php_doc',
                'php_open',
                'print',
                'private',
                'protected',
                'public',
                'require',
                'require_once',
                'return',
                'static',
                'throw',
                'trait',
                'try',
                'use',
                'use_lambda',
                'use_trait',
                'var',
                'while',
                'yield',
                'yield_from',
            ],
        ],
        //                               'declare_strict_types'                          => true
        //'phpdoc_to_return_type' => true,
    ])
    ->setFinder($finder);
