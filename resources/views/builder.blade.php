<div id="{{$id}}"></div>

<input type="hidden" id="{{$id}}_input" name="{{$name}}" value="{{$value}}" />

<style>
  .formio-dialog.formio-dialog-theme-default .formio-dialog-content {
    background: #fff;
  }

  .builder-component .form-group {
    margin-right: 0;
    margin-left: 0px;
  }

  .form-builder-group-header .mt-0 {
    width: 100%;
    height: 100%;
  }

  .formio-component-tabs {
    position: relative;
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-orient: vertical;
    -webkit-box-direction: normal;
    -ms-flex-direction: column;
    flex-direction: column;
    min-width: 0;
    word-wrap: break-word;
    background-color: #fff;
    background-clip: border-box;
    border: 1px solid rgba(0, 0, 0, 0.125);
  }

  .tab-content>.active {
    padding: 1.25rem;
  }

  .formio-component-tabs .nav-tabs {
    border-bottom: 1px solid #ddd;
    background-color: #f5f5f5;
  }

  .component-edit-container .pull-right {
    display: none;
  }
</style>