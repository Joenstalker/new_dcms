@extends('errors::minimal')

@section('title', __('Page Expired'))
@section('code', '419')
@section('message', __('This page was open too long or your session token is no longer valid. Refresh the page, then try your action again.'))
