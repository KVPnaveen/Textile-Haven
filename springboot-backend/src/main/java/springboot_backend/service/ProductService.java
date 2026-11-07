package springboot_backend.service;

import org.springframework.data.domain.Page;
import org.springframework.data.domain.PageRequest;
import org.springframework.data.domain.Pageable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.web.multipart.MultipartFile;
import springboot_backend.dto.ProductDTO;
import springboot_backend.dto.ProductSizeDTO;
import springboot_backend.entity.Product;
import springboot_backend.entity.ProductSize;
import springboot_backend.entity.Size;
import springboot_backend.repository.ProductRepository;
import springboot_backend.repository.ProductSizeRepository;
import springboot_backend.repository.SizeRepository;

import java.math.BigDecimal;
import java.util.*;
import java.util.stream.Collectors;

@Service
public class ProductService {

    private final ProductRepository productRepository;
    private final ProductSizeRepository productSizeRepository;
    private final SizeRepository sizeRepository;
    private final SizeService sizeService;
    private final FileStorageService fileStorageService;

    public ProductService(ProductRepository productRepository,
                          ProductSizeRepository productSizeRepository,
                          SizeRepository sizeRepository,
                          SizeService sizeService,
                          FileStorageService fileStorageService) {
        this.productRepository = productRepository;
        this.productSizeRepository = productSizeRepository;
        this.sizeRepository = sizeRepository;
        this.sizeService = sizeService;
        this.fileStorageService = fileStorageService;
    }

    public Page<ProductDTO> getProducts(String search, int page, int size) {
        Pageable pageable = PageRequest.of(Math.max(0, page - 1), size);
        Page<Product> productPage;

        if (search != null && !search.trim().isEmpty()) {
            productPage = productRepository.searchProducts(search.trim(), pageable);
        } else {
            productPage = productRepository.findAllByOrderByCreatedAtDesc(pageable);
        }

        return productPage.map(this::mapToDTO);
    }

    public List<ProductDTO> getFeaturedProducts(int limit) {
        List<Product> products = productRepository.findTop6ByOrderByCreatedAtDesc();
        if (products.size() > limit) {
            products = products.subList(0, limit);
        }
        return products.stream().map(this::mapToDTO).collect(Collectors.toList());
    }

    public List<ProductDTO> getAllProductsList() {
        return productRepository.findAllByOrderByCreatedAtDesc()
                .stream().map(this::mapToDTO).collect(Collectors.toList());
    }

    public ProductDTO getProductById(Long id) {
        Product product = productRepository.findById(id)
                .orElseThrow(() -> new NoSuchElementException("Product not found with id: " + id));
        return mapToDTO(product);
    }

    @Transactional
    public ProductDTO createProduct(String name, String description, BigDecimal price,
                                   MultipartFile image1, MultipartFile image2,
                                   Map<Long, Integer> sizes) {
        Product product = new Product();
        product.setName(name);
        product.setDescription(description);
        product.setPrice(price);

        if (image1 != null && !image1.isEmpty()) {
            String img1 = fileStorageService.storeFile(image1);
            product.setImage1(img1);
            product.setImage(img1);
        }
        if (image2 != null && !image2.isEmpty()) {
            String img2 = fileStorageService.storeFile(image2);
            product.setImage2(img2);
        }

        product = productRepository.save(product);

        if (sizes != null && !sizes.isEmpty()) {
            List<Size> allSizes = sizeRepository.findAll();
            Map<Long, Size> sizeMap = allSizes.stream().collect(Collectors.toMap(Size::getId, s -> s));

            for (Map.Entry<Long, Integer> entry : sizes.entrySet()) {
                Long sizeId = entry.getKey();
                Integer qty = entry.getValue();
                if (qty != null && qty > 0 && sizeMap.containsKey(sizeId)) {
                    ProductSize ps = new ProductSize(product, sizeMap.get(sizeId), qty);
                    productSizeRepository.save(ps);
                }
            }
        }

        return mapToDTO(product);
    }

    @Transactional
    public ProductDTO updateProduct(Long id, String name, String description, BigDecimal price,
                                   MultipartFile image1, MultipartFile image2,
                                   Map<Long, Integer> sizes) {
        Product product = productRepository.findById(id)
                .orElseThrow(() -> new NoSuchElementException("Product not found with id: " + id));

        product.setName(name);
        product.setDescription(description);
        product.setPrice(price);

        if (image1 != null && !image1.isEmpty()) {
            fileStorageService.deleteFile(product.getImage1());
            String img1 = fileStorageService.storeFile(image1);
            product.setImage1(img1);
            product.setImage(img1);
        }
        if (image2 != null && !image2.isEmpty()) {
            fileStorageService.deleteFile(product.getImage2());
            String img2 = fileStorageService.storeFile(image2);
            product.setImage2(img2);
        }

        product = productRepository.save(product);

        if (sizes != null) {
            List<Size> allSizes = sizeRepository.findAll();
            Map<Long, Size> sizeMap = allSizes.stream().collect(Collectors.toMap(Size::getId, s -> s));
            List<ProductSize> existingProductSizes = productSizeRepository.findByProductId(id);
            Map<Long, ProductSize> existingMap = existingProductSizes.stream()
                    .collect(Collectors.toMap(ps -> ps.getSize().getId(), ps -> ps));

            for (Map.Entry<Long, Size> entry : sizeMap.entrySet()) {
                Long sizeId = entry.getKey();
                Integer qty = sizes.getOrDefault(sizeId, 0);
                ProductSize ps = existingMap.get(sizeId);

                if (ps != null) {
                    ps.setStockQuantity(qty != null ? Math.max(0, qty) : 0);
                    productSizeRepository.save(ps);
                } else if (qty != null && qty > 0) {
                    ProductSize newPs = new ProductSize(product, entry.getValue(), qty);
                    productSizeRepository.save(newPs);
                }
            }
        }

        return mapToDTO(product);
    }

    @Transactional
    public void deleteProduct(Long id) {
        Product product = productRepository.findById(id)
                .orElseThrow(() -> new NoSuchElementException("Product not found with id: " + id));

        fileStorageService.deleteFile(product.getImage1());
        fileStorageService.deleteFile(product.getImage2());
        productSizeRepository.deleteByProductId(id);
        productRepository.delete(product);
    }

    public ProductDTO mapToDTO(Product product) {
        ProductDTO dto = new ProductDTO();
        dto.setId(product.getId());
        dto.setName(product.getName());
        dto.setDescription(product.getDescription());
        dto.setPrice(product.getPrice());
        dto.setImage(product.getImage());
        dto.setImage1(product.getImage1());
        dto.setImage2(product.getImage2());
        dto.setCreatedAt(product.getCreatedAt());

        String main = product.getImage1() != null ? product.getImage1() : product.getImage();
        String secondary = product.getImage2();

        dto.setMainImage(formatImagePath(main));
        dto.setSecondaryImage(formatImagePath(secondary));

        List<ProductSizeDTO> sizes = sizeService.getProductSizes(product.getId());
        dto.setSizes(sizes);

        int totalStock = sizes.stream().mapToInt(ProductSizeDTO::getStockQuantity).sum();
        dto.setTotalStock(totalStock);

        return dto;
    }

    private String formatImagePath(String path) {
        if (path == null || path.isBlank()) return null;
        if (path.startsWith("http://") || path.startsWith("https://")) {
            return path;
        }
        if (path.startsWith("/")) {
            return path;
        }
        return "/" + path;
    }
}

