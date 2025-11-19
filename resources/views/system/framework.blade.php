@extends('layouts/contentNavbarLayout')

@section('title', 'Framework System')

@section('content')
<style>
    /* Container untuk zoom & pan */
    #zoom-wrapper {
        width: 100%;
        height: 80vh;
        border: 1px solid #ddd;
        overflow: hidden;
        position: relative;
        background: #f8f8f8;
        cursor: grab;
    }

    #zoom-wrapper:active {
        cursor: grabbing;
    }

    /* Elemen yang di-zoom */
    #zoom-content {
        transform-origin: center center;
        transition: transform 0.1s linear;
    }
</style>

<div class="container py-4">
    <h4 class="mb-4">Framework System</h4>

    <div class="card">
        <div class="card-body">

            <div id="zoom-wrapper">
                <div id="zoom-content">
                    <object
                        id="svg-viewer"
                        data="{{ asset('storage/' . $svgPath) }}"
                        type="image/svg+xml"
                        style="width: 100%; height: auto;">
                        <img src="{{ asset('storage/' . $svgPath) }}"
                             alt="Framework System"
                             class="img-fluid">
                    </object>
                </div>
            </div>

            <div class="text-center mt-3">
                <button class="btn btn-primary btn-sm" onclick="zoomIn()">Zoom In</button>
                <button class="btn btn-secondary btn-sm" onclick="zoomOut()">Zoom Out</button>
                <button class="btn btn-dark btn-sm" onclick="resetZoom()">Reset</button>
            </div>

        </div>
    </div>
</div>

<script>
    let scale = 1;
    let wrapper = document.getElementById('zoom-wrapper');
    let content = document.getElementById('zoom-content');

    function applyZoom() {
        content.style.transform = `scale(${scale})`;
    }

    function zoomIn() {
        scale += 0.1;
        applyZoom();
    }

    function zoomOut() {
        if (scale > 0.2) scale -= 0.1;
        applyZoom();
    }

    function resetZoom() {
        scale = 1;
        posX = 0;
        posY = 0;
        content.style.transform = `translate(0px, 0px) scale(${scale})`;
    }

    // ==== DRAG / PAN ====
    let posX = 0, posY = 0;
    let isDragging = false;
    let startX, startY;

    wrapper.addEventListener("mousedown", function(e) {
        isDragging = true;
        startX = e.clientX - posX;
        startY = e.clientY - posY;
    });

    wrapper.addEventListener("mousemove", function(e) {
        if (isDragging) {
            posX = e.clientX - startX;
            posY = e.clientY - startY;
            content.style.transform = `translate(${posX}px, ${posY}px) scale(${scale})`;
        }
    });

    wrapper.addEventListener("mouseup", () => isDragging = false);
    wrapper.addEventListener("mouseleave", () => isDragging = false);

    // ==== ZOOM MOUSE WHEEL ====
    wrapper.addEventListener("wheel", function(e) {
        e.preventDefault();

        if (e.deltaY < 0) {
            scale += 0.1; // scroll up
        } else {
            if (scale > 0.2) scale -= 0.1; // scroll down
        }

        applyZoom();
    });
</script>
@endsection
