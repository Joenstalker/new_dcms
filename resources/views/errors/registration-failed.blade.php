@extends('errors::minimal')

@section('title', $title ?? 'Registration Error')
@section('code', $code ?? '500')
@section('message', $message ?? 'An error occurred during registration. Please contact support.')
